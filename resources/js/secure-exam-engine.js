/**
 * SecureExamEngine v2.0 — AvaliaFA
 * Monitora e protege a sessão de prova no browser.
 *
 * v2 adiciona:
 *  - Detecção de inatividade (aviso 2 min, violação 3 min)
 *  - Detecção de múltiplos monitores (via screen.isExtended)
 *  - DeviceFingerprint enviado ao iniciar
 *
 * Ativação via meta tag:
 * <meta name="avalia-fa-exam"
 *   data-session-id="123"
 *   data-max-violations="3"
 *   data-snapshot-interval="300000"
 *   data-webcam-enabled="true"
 *   data-inactivity-warning="120"
 *   data-inactivity-timeout="180"
 *   data-api-base="/exam">
 */

(function () {
    'use strict';

    const meta = document.querySelector('meta[name="avalia-fa-exam"]');
    if (!meta) return;

    function isMobileDevice() {
        const ua = navigator.userAgent || '';
        const mobileUa = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(ua);
        const narrowViewport = window.matchMedia('(max-width: 768px)').matches;

        return mobileUa || narrowViewport;
    }

    const config = {
        sessionId:                meta.dataset.sessionId,
        maxViolations:            parseInt(meta.dataset.maxViolations        ?? '3',      10),
        snapshotInterval:         parseInt(meta.dataset.snapshotInterval     ?? '300000', 10),
        webcamEnabled:            meta.dataset.webcamEnabled === 'true',
        fullscreenRequired:       meta.dataset.fullscreenRequired !== 'false',
        simulationMode:           meta.dataset.simulationMode === 'true',
        inactivityWarning:        parseInt(meta.dataset.inactivityWarning    ?? '120',    10),
        inactivityTimeout:        parseInt(meta.dataset.inactivityTimeout    ?? '180',    10),
        csrfToken:                document.querySelector('meta[name="csrf-token"]')?.content ?? '',
        apiBase:                  meta.dataset.apiBase || '/exam',
        faceRecognitionEnabled:   meta.dataset.faceRecognitionEnabled === 'true',
        faceReferenceUrl:         meta.dataset.faceReferenceUrl ?? '',
        faceCheckIntervalMs:      parseInt(meta.dataset.faceCheckIntervalMs  ?? '120000', 10),
    };

    config.mobileDevice = isMobileDevice();
    if (config.mobileDevice) {
        config.fullscreenRequired = false;
    }

    const WEBCAM_HEALTH_INTERVAL = 15000;
    const WEBCAM_OBSTRUCTION_CONFIRMATIONS = 3;
    const SNAPSHOT_CAPTURE_TIMEOUT = 4000;

    let violationCount      = 0;
    let webcamStream        = null;
    let snapshotTimer       = null;
    let webcamHealthTimer   = null;
    let webcamVideoElement  = null;
    let webcamObstructionCount = 0;
    let webcamObstructionActive = false;
    let isTerminated        = false;

    // Inatividade
    let inactivityTimer     = null;
    let inactivityWarned    = false;
    let lastActivityAt      = Date.now();

    // Reconhecimento facial
    let faceReferenceDescriptor = null;
    let faceCheckTimer          = null;
    let faceModelsLoaded        = false;

    // Múltiplos monitores
    let monitorCheckTimer   = null;
    let eventQueuePendingCount = 0;
    let snapshotQueuePendingCount = 0;
    let dbPromise           = null;
    let initialized         = false;

    function emitQueueStatus() {
        window.dispatchEvent(new CustomEvent('secureexam:queue-status', {
            detail: {
                pending: eventQueuePendingCount + snapshotQueuePendingCount,
                security_pending: eventQueuePendingCount,
                snapshot_pending: snapshotQueuePendingCount,
                online: navigator.onLine,
            },
        }));
    }

    function openQueueDb() {
        return new Promise((resolve) => {
            if (!window.indexedDB) {
                resolve(null);
                return;
            }

            const request = window.indexedDB.open('avaliafa-offline', 1);

            request.onupgradeneeded = () => {
                const db = request.result;
                if (!db.objectStoreNames.contains('security_events')) {
                    db.createObjectStore('security_events', { keyPath: 'id', autoIncrement: true });
                }
                if (!db.objectStoreNames.contains('snapshot_uploads')) {
                    db.createObjectStore('snapshot_uploads', { keyPath: 'id', autoIncrement: true });
                }
            };

            request.onsuccess = () => resolve(request.result);
            request.onerror = () => resolve(null);
        });
    }

    async function getDb() {
        if (!dbPromise) {
            dbPromise = openQueueDb();
        }

        return dbPromise;
    }

    async function updatePendingCount() {
        const db = await getDb();

        if (!db) {
            eventQueuePendingCount = 0;
            snapshotQueuePendingCount = 0;
            emitQueueStatus();
            return;
        }

        await new Promise((resolve) => {
            const tx = db.transaction(['security_events', 'snapshot_uploads'], 'readonly');
            const eventsRequest = tx.objectStore('security_events').count();
            const snapshotsRequest = tx.objectStore('snapshot_uploads').count();
            let eventsReady = false;
            let snapshotsReady = false;

            const finish = () => {
                if (!eventsReady || !snapshotsReady) {
                    return;
                }

                emitQueueStatus();
                resolve();
            };

            eventsRequest.onsuccess = () => {
                eventQueuePendingCount = eventsRequest.result ?? 0;
                eventsReady = true;
                finish();
            };

            eventsRequest.onerror = () => {
                eventQueuePendingCount = 0;
                eventsReady = true;
                finish();
            };

            snapshotsRequest.onsuccess = () => {
                snapshotQueuePendingCount = snapshotsRequest.result ?? 0;
                snapshotsReady = true;
                finish();
            };

            snapshotsRequest.onerror = () => {
                snapshotQueuePendingCount = 0;
                snapshotsReady = true;
                finish();
            };
        });
    }

    async function queueEvent(eventPayload) {
        const db = await getDb();

        if (!db) {
            return;
        }

        await new Promise((resolve) => {
            const tx = db.transaction('security_events', 'readwrite');
            tx.objectStore('security_events').add(eventPayload);
            tx.oncomplete = resolve;
            tx.onerror = resolve;
        });

        await updatePendingCount();
    }

    async function sendEvent(payload) {
        const response = await fetch(`${config.apiBase}/sessions/${config.sessionId}/security-event`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': config.csrfToken,
                'Accept':       'application/json',
            },
            body:      JSON.stringify({ type: payload.type, metadata: payload.metadata }),
            keepalive: true,
        });

        return response.ok;
    }

    async function queueSnapshot(snapshotPayload) {
        const db = await getDb();

        if (!db) {
            return;
        }

        await new Promise((resolve) => {
            const tx = db.transaction('snapshot_uploads', 'readwrite');
            tx.objectStore('snapshot_uploads').add(snapshotPayload);
            tx.oncomplete = resolve;
            tx.onerror = resolve;
        });

        await updatePendingCount();
    }

    async function sendSnapshot(payload) {
        const form = new FormData();
        form.append('snapshot', payload.blob, payload.filename ?? 'snapshot.jpg');
        form.append('trigger', payload.trigger ?? 'scheduled');
        form.append('captured_at', payload.captured_at ?? new Date().toISOString());

        const response = await fetch(`${config.apiBase}/sessions/${config.sessionId}/snapshot`, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': config.csrfToken },
            body: form,
            keepalive: true,
        });

        return response.ok;
    }

    async function flushEventQueue() {
        if (!navigator.onLine) {
            emitQueueStatus();
            return;
        }

        const db = await getDb();

        if (!db) {
            emitQueueStatus();
            return;
        }

        let shouldContinue = true;

        while (shouldContinue) {
            const nextEvent = await new Promise((resolve) => {
                const tx = db.transaction('security_events', 'readonly');
                const request = tx.objectStore('security_events').openCursor();
                request.onsuccess = () => {
                    const cursor = request.result;
                    resolve(cursor ? { id: cursor.key, payload: cursor.value } : null);
                };
                request.onerror = () => resolve(null);
            });

            if (!nextEvent) {
                break;
            }

            try {
                const delivered = await sendEvent(nextEvent.payload);

                if (!delivered) {
                    shouldContinue = false;
                    break;
                }
            } catch (e) {
                shouldContinue = false;
                break;
            }

            await new Promise((resolve) => {
                const tx = db.transaction('security_events', 'readwrite');
                tx.objectStore('security_events').delete(nextEvent.id);
                tx.oncomplete = resolve;
                tx.onerror = resolve;
            });
        }

        await updatePendingCount();
    }

    async function flushSnapshotQueue() {
        if (!navigator.onLine) {
            emitQueueStatus();
            return;
        }

        const db = await getDb();

        if (!db) {
            emitQueueStatus();
            return;
        }

        let shouldContinue = true;

        while (shouldContinue) {
            const nextSnapshot = await new Promise((resolve) => {
                const tx = db.transaction('snapshot_uploads', 'readonly');
                const request = tx.objectStore('snapshot_uploads').openCursor();
                request.onsuccess = () => {
                    const cursor = request.result;
                    resolve(cursor ? { id: cursor.key, payload: cursor.value } : null);
                };
                request.onerror = () => resolve(null);
            });

            if (!nextSnapshot) {
                break;
            }

            try {
                const delivered = await sendSnapshot(nextSnapshot.payload);

                if (!delivered) {
                    shouldContinue = false;
                    break;
                }
            } catch (e) {
                shouldContinue = false;
                break;
            }

            await new Promise((resolve) => {
                const tx = db.transaction('snapshot_uploads', 'readwrite');
                tx.objectStore('snapshot_uploads').delete(nextSnapshot.id);
                tx.oncomplete = resolve;
                tx.onerror = resolve;
            });
        }

        await updatePendingCount();
    }

    // -------------------------------------------------------------------------
    // Event reporting
    // -------------------------------------------------------------------------

    async function reportEvent(type, metadata = {}) {
        if (isTerminated) return;

        const payload = {
            session_id: config.sessionId,
            type,
            metadata,
            captured_at: new Date().toISOString(),
        };

        try {
            if (!navigator.onLine) {
                await queueEvent(payload);
                return;
            }

            const delivered = await sendEvent(payload);
            if (!delivered) {
                await queueEvent(payload);
                return;
            }

            await flushEventQueue();
        } catch (e) {
            await queueEvent(payload);
        }
    }

    // -------------------------------------------------------------------------
    // Violation handling
    // -------------------------------------------------------------------------

    function recordViolation(type, metadata = {}) {
        if (isTerminated) return;

        if (config.simulationMode) {
            reportEvent(type, { ...metadata, simulation_mode: true });
            showWarning(
                'Evento registrado no simulado',
                'O monitoramento foi registrado, mas simulados não são bloqueados por violações.'
            );
            return;
        }

        violationCount++;
        reportEvent(type, { ...metadata, violation_count: violationCount });

        if (config.webcamEnabled) {
            captureSnapshot('violation');
        }

        const remaining = config.maxViolations - violationCount;

        if (remaining <= 0) {
            reportEvent('violation_limit_reached', { violation_count: violationCount });
            terminateSession('Limite de violações atingido.');
            return;
        }

        showWarning(
            '⚠️ Advertência de segurança',
            'Você recebeu uma advertência. Em caso de recorrência, a prova poderá ser encerrada automaticamente.'
        );
    }

    function terminateSession(reason) {
        if (isTerminated) return;
        isTerminated = true;

        stopWebcam();
        stopFaceVerification();
        clearInterval(snapshotTimer);
        clearTimeout(inactivityTimer);
        clearInterval(monitorCheckTimer);

        showOverlay(`🚫 Prova encerrada automaticamente.\n\n${reason}`);
        reportEvent('violation_limit_reached', { reason });

        setTimeout(() => {
            window.location.href = `${config.apiBase}/sessions/${config.sessionId}/terminated`;
        }, 4000);
    }

    // -------------------------------------------------------------------------
    // Fullscreen enforcement
    // -------------------------------------------------------------------------

    function requestFullscreen() {
        const el  = document.documentElement;
        const req = el.requestFullscreen ?? el.webkitRequestFullscreen ?? el.mozRequestFullScreen;
        if (req) req.call(el).catch(() => recordViolation('fullscreen_denied'));
    }

    function ensureFullscreen() {
        if (!config.fullscreenRequired) {
            return;
        }

        const isFull = !!(
            document.fullscreenElement ||
            document.webkitFullscreenElement ||
            document.mozFullScreenElement
        );

        if (!isFull) {
            requestFullscreen();
        }
    }

    function onFullscreenChange() {
        if (!config.fullscreenRequired) {
            return;
        }

        const isFull = !!(
            document.fullscreenElement      ||
            document.webkitFullscreenElement ||
            document.mozFullScreenElement
        );

        if (!isFull && !isTerminated) {
            recordViolation('fullscreen_exit');
            requestFullscreen();
        }
    }

    document.addEventListener('fullscreenchange',       onFullscreenChange);
    document.addEventListener('webkitfullscreenchange', onFullscreenChange);
    document.addEventListener('mozfullscreenchange',    onFullscreenChange);

    // -------------------------------------------------------------------------
    // Tab / window visibility
    // -------------------------------------------------------------------------

    document.addEventListener('visibilitychange', () => {
        if (document.hidden) recordViolation('tab_switch');
    });

    window.addEventListener('blur', () => {
        if (!isTerminated) recordViolation('window_blur');
    });

    // -------------------------------------------------------------------------
    // Inatividade (v2)
    // -------------------------------------------------------------------------

    const ACTIVITY_EVENTS = ['mousemove', 'mousedown', 'keydown', 'touchstart', 'scroll'];

    function resetInactivity() {
        lastActivityAt   = Date.now();
        inactivityWarned = false;

        clearTimeout(inactivityTimer);
        scheduleInactivityCheck();
    }

    function scheduleInactivityCheck() {
        inactivityTimer = setTimeout(() => {
            const idleSeconds = Math.floor((Date.now() - lastActivityAt) / 1000);

            if (idleSeconds >= config.inactivityTimeout) {
                recordViolation('inactivity_timeout', { idle_seconds: idleSeconds });
                resetInactivity();
            } else if (idleSeconds >= config.inactivityWarning && !inactivityWarned) {
                inactivityWarned = true;
                reportEvent('inactivity_warning', { idle_seconds: idleSeconds });
                showWarning(
                    '🕐 Você está inativo',
                    `Nenhuma atividade detectada há ${idleSeconds}s. Você tem ${config.inactivityTimeout - idleSeconds}s antes de receber uma advertência.`
                );
                scheduleInactivityCheck();
            } else {
                scheduleInactivityCheck();
            }
        }, 10000); // Verifica a cada 10 segundos
    }

    ACTIVITY_EVENTS.forEach(evt =>
        document.addEventListener(evt, resetInactivity, { passive: true })
    );

    // -------------------------------------------------------------------------
    // Múltiplos monitores (v2) — screen.isExtended API
    // -------------------------------------------------------------------------

    let multiMonitorReported = false;

    function flagMultiMonitor(detail) {
        if (multiMonitorReported) return;
        multiMonitorReported = true;
        reportEvent('possible_second_monitor', detail);
        recordViolation('possible_second_monitor', detail);
    }

    function checkMultipleMonitors() {
        if (typeof screen === 'undefined') return;

        // 1) screen.isExtended — melhor API (Chrome 100+)
        if ('isExtended' in screen && screen.isExtended) {
            flagMultiMonitor({
                method:        'screen.isExtended',
                screen_width:  screen.width,
                screen_height: screen.height,
                is_extended:   true,
            });
            return;
        }

        // 2) Heurística: janela posicionada além dos limites da tela primária
        //    (indica que o navegador está num monitor secundário)
        if (typeof window.screenLeft !== 'undefined') {
            const beyondRight = window.screenLeft > screen.width;
            const beyondLeft  = window.screenLeft + window.outerWidth < 0;
            const beyondDown  = window.screenTop > screen.height;

            if (beyondRight || beyondLeft || beyondDown) {
                flagMultiMonitor({
                    method:       'window_position',
                    screenLeft:   window.screenLeft,
                    screenTop:    window.screenTop,
                    outerWidth:   window.outerWidth,
                    screen_width: screen.width,
                    screen_height: screen.height,
                });
                return;
            }
        }

        // 3) Heurística: availWidth difere muito de width
        //    (SO pode reportar desktop virtual estendido)
        if (screen.availWidth && screen.width) {
            const ratio = screen.availWidth / screen.width;
            if (ratio > 1.5) {
                flagMultiMonitor({
                    method:      'availWidth_ratio',
                    availWidth:  screen.availWidth,
                    screenWidth: screen.width,
                    ratio:       Math.round(ratio * 100) / 100,
                });
                return;
            }
        }

        // 4) Window Management API (getScreenDetails) — Chrome 100+
        if ('getScreenDetails' in window && !multiMonitorReported) {
            window.getScreenDetails().then(details => {
                if (details.screens && details.screens.length > 1) {
                    flagMultiMonitor({
                        method:       'getScreenDetails',
                        screen_count: details.screens.length,
                        screens:      details.screens.map(s => ({
                            width:  s.width,
                            height: s.height,
                            label:  s.label || '',
                        })),
                    });
                }
            }).catch(() => {
                // Permissão negada — não faz nada
            });
        }
    }

    // -------------------------------------------------------------------------
    // Keyboard shortcut blocking
    // -------------------------------------------------------------------------

    const BLOCKED_KEYS = new Set(['F12', 'F5', 'PrintScreen']);

    const BLOCKED_COMBOS = [
        (e) => e.ctrlKey  && e.shiftKey && ['I', 'J', 'C', 'K'].includes(e.key.toUpperCase()),
        (e) => e.ctrlKey  && e.key.toUpperCase() === 'U',
        (e) => e.ctrlKey  && e.key.toUpperCase() === 'S',
        (e) => e.ctrlKey  && e.key.toUpperCase() === 'P',
        (e) => e.altKey   && e.key === 'Tab',
        (e) => e.metaKey  && e.shiftKey && e.key.toUpperCase() === 'I',
    ];

    document.addEventListener('keydown', (e) => {
        if (BLOCKED_KEYS.has(e.key) || BLOCKED_COMBOS.some(fn => fn(e))) {
            e.preventDefault();
            e.stopPropagation();
            recordViolation('shortcut_blocked', { key: e.key });
        }
    }, true);

    // -------------------------------------------------------------------------
    // Right-click blocking
    // -------------------------------------------------------------------------

    document.addEventListener('contextmenu', (e) => {
        e.preventDefault();
        reportEvent('right_click_blocked');
    });

    // -------------------------------------------------------------------------
    // Copy / paste / select blocking
    // -------------------------------------------------------------------------

    ['copy', 'cut', 'paste', 'selectstart'].forEach(evt =>
        document.addEventListener(evt, (e) => e.preventDefault())
    );

    // -------------------------------------------------------------------------
    // DeviceFingerprint (v2)
    // -------------------------------------------------------------------------

    function buildFingerprint() {
        const parts = [
            navigator.userAgent,
            navigator.language,
            screen.colorDepth,
            screen.width + 'x' + screen.height,
            new Date().getTimezoneOffset(),
            navigator.hardwareConcurrency ?? 0,
        ].join('|');

        // Simple hash (não criptográfico, apenas identificação)
        let hash = 0;
        for (let i = 0; i < parts.length; i++) {
            hash = ((hash << 5) - hash) + parts.charCodeAt(i);
            hash |= 0;
        }

        return Math.abs(hash).toString(16).padStart(8, '0');
    }

    async function sendFingerprint() {
        const fingerprint = buildFingerprint();
        const metadata    = {
            user_agent:         navigator.userAgent,
            language:           navigator.language,
            screen:             `${screen.width}x${screen.height}`,
            color_depth:        screen.colorDepth,
            timezone_offset:    new Date().getTimezoneOffset(),
            hardware_concurrency: navigator.hardwareConcurrency,
            device_memory:      navigator.deviceMemory ?? null,
            touch_points:       navigator.maxTouchPoints ?? 0,
        };

        try {
            await fetch(`${config.apiBase}/sessions/${config.sessionId}/fingerprint`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': config.csrfToken,
                },
                body:      JSON.stringify({ fingerprint, metadata }),
                keepalive: true,
            });
        } catch (e) { /* silencioso */ }
    }

    // -------------------------------------------------------------------------
    // Webcam capture
    // -------------------------------------------------------------------------

    function getWebcamVideoElement() {
        if (webcamVideoElement) {
            return webcamVideoElement;
        }

        webcamVideoElement = document.getElementById('webcam-preview');

        if (!webcamVideoElement) {
            webcamVideoElement = document.createElement('video');
            webcamVideoElement.muted = true;
            webcamVideoElement.playsInline = true;
            webcamVideoElement.autoplay = true;
            webcamVideoElement.style.cssText = 'position:fixed;bottom:0;right:0;width:1px;height:1px;opacity:0;pointer-events:none;';
            document.body.appendChild(webcamVideoElement);
        }

        return webcamVideoElement;
    }

    async function waitForWebcamVideo(video, timeoutMs = SNAPSHOT_CAPTURE_TIMEOUT) {
        if (video.readyState >= HTMLMediaElement.HAVE_CURRENT_DATA && video.videoWidth > 0) {
            return;
        }

        await new Promise((resolve, reject) => {
            const timeoutId = window.setTimeout(() => {
                cleanup();
                reject(new Error('webcam_frame_timeout'));
            }, timeoutMs);

            const onLoaded = () => {
                cleanup();
                resolve();
            };

            const onError = () => {
                cleanup();
                reject(new Error('webcam_frame_unavailable'));
            };

            const cleanup = () => {
                window.clearTimeout(timeoutId);
                video.removeEventListener('loadeddata', onLoaded);
                video.removeEventListener('error', onError);
            };

            video.addEventListener('loadeddata', onLoaded, { once: true });
            video.addEventListener('error', onError, { once: true });
        });
    }

    function inspectCanvasFrame(canvas) {
        const context = canvas.getContext('2d', { willReadFrequently: true });

        if (!context) {
            return { obstructed: false, averageBrightness: 0, dynamicRange: 0 };
        }

        const pixels = context.getImageData(0, 0, canvas.width, canvas.height).data;
        let totalBrightness = 0;
        let minBrightness = 255;
        let maxBrightness = 0;
        let samples = 0;

        for (let index = 0; index < pixels.length; index += 16) {
            const brightness = (pixels[index] + pixels[index + 1] + pixels[index + 2]) / 3;
            totalBrightness += brightness;
            minBrightness = Math.min(minBrightness, brightness);
            maxBrightness = Math.max(maxBrightness, brightness);
            samples++;
        }

        const averageBrightness = samples > 0 ? totalBrightness / samples : 0;
        const dynamicRange = maxBrightness - minBrightness;
        const obstructed = dynamicRange < 18 && (averageBrightness < 32 || averageBrightness > 223);

        return { obstructed, averageBrightness, dynamicRange };
    }

    async function inspectLiveWebcamFrame() {
        if (!webcamStream) {
            return { obstructed: false, averageBrightness: 0, dynamicRange: 0 };
        }

        const video = getWebcamVideoElement();
        if (video.srcObject !== webcamStream) {
            video.srcObject = webcamStream;
        }

        await video.play().catch(() => {});
        await waitForWebcamVideo(video);

        const canvas = document.createElement('canvas');
        canvas.width = 160;
        canvas.height = 120;

        const context = canvas.getContext('2d');
        if (!context) {
            return { obstructed: false, averageBrightness: 0, dynamicRange: 0 };
        }

        context.drawImage(video, 0, 0, canvas.width, canvas.height);

        return inspectCanvasFrame(canvas);
    }

    async function updateWebcamObstructionState(inspection, trigger) {
        if (!inspection.obstructed) {
            webcamObstructionCount = 0;
            webcamObstructionActive = false;
            return;
        }

        webcamObstructionCount++;

        if (webcamObstructionCount < WEBCAM_OBSTRUCTION_CONFIRMATIONS || webcamObstructionActive) {
            return;
        }

        webcamObstructionActive = true;

        await reportEvent('webcam_obstructed', {
            trigger,
            consecutive_frames: webcamObstructionCount,
            average_brightness: Math.round(inspection.averageBrightness),
            dynamic_range: Math.round(inspection.dynamicRange),
        });

        showWarning(
            'Camera sem imagem valida',
            'Sua camera parece obstruida. Remova a tampa ou ajuste a iluminacao para continuar.'
        );
    }

    function scheduleWebcamHealthChecks() {
        clearInterval(webcamHealthTimer);

        webcamHealthTimer = setInterval(async () => {
            if (!webcamStream || document.hidden) {
                return;
            }

            try {
                const inspection = await inspectLiveWebcamFrame();
                await updateWebcamObstructionState(inspection, 'healthcheck');
            } catch (e) {
                // Ignore transient frame read failures.
            }
        }, WEBCAM_HEALTH_INTERVAL);
    }

    async function initWebcam() {
        if (!config.webcamEnabled) return;

        try {
            // Reuse stream already granted by the security gate overlay
            if (window.__avaliaWebcamStream) {
                webcamStream = window.__avaliaWebcamStream;
                delete window.__avaliaWebcamStream;
            } else {
                webcamStream = await navigator.mediaDevices.getUserMedia({ video: true, audio: false });
            }
            const video = getWebcamVideoElement();
            video.srcObject = webcamStream;
            await inspectLiveWebcamFrame()
                .then((inspection) => updateWebcamObstructionState(inspection, 'start'))
                .catch(() => {});
            scheduleSnapshots();
            scheduleWebcamHealthChecks();
        } catch (err) {
            reportEvent('webcam_unavailable', { error: err.message });
        }
    }

    function captureSnapshot(trigger = 'scheduled') {
        if (!webcamStream) {
            return Promise.resolve(false);
        }

        const video  = getWebcamVideoElement();
        const canvas = document.createElement('canvas');

        if (video.srcObject !== webcamStream) {
            video.srcObject = webcamStream;
        }

        return video.play().then(() => waitForWebcamVideo(video)).then(() => new Promise((resolve) => {
            canvas.width  = 320;
            canvas.height = 240;
            canvas.getContext('2d').drawImage(video, 0, 0, 320, 240);
            const inspection = inspectCanvasFrame(canvas);

            canvas.toBlob(async (blob) => {
                if (!blob) {
                    resolve(false);
                    return;
                }

                await updateWebcamObstructionState(inspection, trigger);

                const payload = {
                    session_id: config.sessionId,
                    blob,
                    filename: `snapshot-${Date.now()}.jpg`,
                    trigger,
                    captured_at: new Date().toISOString(),
                };

                try {
                    if (!navigator.onLine) {
                        await queueSnapshot(payload);
                        resolve(true);
                        return;
                    }

                    const delivered = await sendSnapshot(payload);
                    if (!delivered) {
                        await queueSnapshot(payload);
                        resolve(true);
                        return;
                    }

                    await flushSnapshotQueue();
                    resolve(true);
                } catch (e) {
                    await queueSnapshot(payload);
                    resolve(true);
                }
            }, 'image/jpeg', 0.7);
        })).catch(() => {
            reportEvent('webcam_unavailable', { error: 'snapshot_frame_unavailable' });
            return false;
        });
    }

    function scheduleSnapshots() {
        captureSnapshot('start');
        snapshotTimer = setInterval(() => captureSnapshot('scheduled'), config.snapshotInterval);
    }

    function stopWebcam() {
        webcamStream?.getTracks().forEach(t => t.stop());
        webcamStream = null;
        clearInterval(snapshotTimer);
        clearInterval(webcamHealthTimer);
    }

    // -------------------------------------------------------------------------
    // Reconhecimento facial
    // -------------------------------------------------------------------------

    async function initFaceVerification() {
        if (!config.faceRecognitionEnabled) return;
        if (typeof faceapi === 'undefined') {
            console.warn('[AvaliaFA Face] face-api.js não carregado.');
            await reportFaceVerification('enrollment', 'skipped', null);
            return;
        }

        try {
            await Promise.all([
                faceapi.nets.tinyFaceDetector.loadFromUri('/vendor/face-api/models'),
                faceapi.nets.faceLandmark68TinyNet.loadFromUri('/vendor/face-api/models'),
                faceapi.nets.faceRecognitionNet.loadFromUri('/vendor/face-api/models'),
            ]);

            faceModelsLoaded = true;

            if (config.faceReferenceUrl) {
                // Foto de referência cadastrada pelo admin — carrega e compara
                await loadFaceReferenceDescriptor();
            } else {
                // Sem foto cadastrada — captura do próprio aluno como referência inicial
                await captureSelfReference();
            }

            if (faceReferenceDescriptor) {
                await runFaceCheck('enrollment');
                faceCheckTimer = setInterval(() => runFaceCheck('check'), config.faceCheckIntervalMs);
            }
        } catch (err) {
            console.error('[AvaliaFA Face] Erro ao inicializar:', err);
            await reportFaceVerification('enrollment', 'skipped', null);
        }
    }

    async function loadFaceReferenceDescriptor() {
        try {
            const img = await faceapi.fetchImage(config.faceReferenceUrl);
            const detection = await faceapi
                .detectSingleFace(img, new faceapi.TinyFaceDetectorOptions())
                .withFaceLandmarks(true)
                .withFaceDescriptor();

            if (!detection) {
                console.warn('[AvaliaFA Face] Foto de referência sem rosto detectado.');
                return;
            }

            faceReferenceDescriptor = detection.descriptor;
        } catch (err) {
            console.error('[AvaliaFA Face] Erro ao carregar referência:', err);
        }
    }

    /**
     * Sem foto cadastrada: exibe overlay pedindo ao aluno para posicionar o rosto,
     * captura o frame, extrai o descriptor e salva no servidor como referência.
     * Tenta até 3 vezes se não detectar rosto.
     */
    async function captureSelfReference() {
        return new Promise((resolve) => {
            const overlay = document.createElement('div');
            overlay.id = 'face-selfref-overlay';
            overlay.style.cssText = [
                'position:fixed', 'inset:0', 'background:rgba(15,23,42,0.92)',
                'display:flex', 'align-items:center', 'justify-content:center',
                'z-index:999990', 'font-family:inherit',
            ].join(';');

            overlay.innerHTML = `
                <div style="background:#fff;border-radius:20px;padding:40px 36px;max-width:420px;width:90%;text-align:center">
                    <div style="width:56px;height:56px;background:#EFF6FF;border-radius:14px;display:flex;align-items:center;justify-content:center;margin:0 auto 20px">
                        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#2563EB" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>
                        </svg>
                    </div>
                    <h3 style="font-size:1.1rem;font-weight:700;color:#0F172A;margin:0 0 10px">Verificação de identidade</h3>
                    <p style="font-size:0.88rem;color:#64748B;line-height:1.6;margin:0 0 24px">
                        Sua identidade ainda não foi cadastrada no sistema.<br>
                        Posicione seu rosto centralizado na câmera e clique em <strong>Registrar</strong>.
                        Esta foto será usada para verificação durante toda a prova.
                    </p>
                    <div id="face-selfref-preview" style="width:200px;height:150px;background:#F1F5F9;border-radius:12px;margin:0 auto 20px;overflow:hidden;display:flex;align-items:center;justify-content:center">
                        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#CBD5E1" stroke-width="1.5"><rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8M12 17v4"/></svg>
                    </div>
                    <p id="face-selfref-status" style="font-size:0.82rem;color:#64748B;margin:0 0 16px;min-height:20px"></p>
                    <button id="face-selfref-btn" style="background:#2563EB;color:#fff;border:none;border-radius:10px;padding:12px 32px;font-size:0.93rem;font-weight:600;cursor:pointer;width:100%">
                        📷 Registrar minha face
                    </button>
                </div>`;

            document.body.appendChild(overlay);

            // Mostra preview da webcam no overlay
            const previewDiv = overlay.querySelector('#face-selfref-preview');
            const statusEl   = overlay.querySelector('#face-selfref-status');
            const btn        = overlay.querySelector('#face-selfref-btn');

            if (webcamStream) {
                const previewVideo = document.createElement('video');
                previewVideo.srcObject = webcamStream;
                previewVideo.muted = true;
                previewVideo.autoplay = true;
                previewVideo.playsInline = true;
                previewVideo.style.cssText = 'width:100%;height:100%;object-fit:cover';
                previewDiv.innerHTML = '';
                previewDiv.appendChild(previewVideo);
            }

            let attempts = 0;

            btn.addEventListener('click', async () => {
                btn.disabled = true;
                btn.textContent = 'Analisando...';
                statusEl.textContent = '';
                attempts++;

                try {
                    const video = getWebcamVideoElement();
                    await waitForWebcamVideo(video);
                    const canvas = faceapi.createCanvasFromMedia(video);

                    const detection = await faceapi
                        .detectSingleFace(canvas, new faceapi.TinyFaceDetectorOptions())
                        .withFaceLandmarks(true)
                        .withFaceDescriptor();

                    if (!detection) {
                        statusEl.style.color = '#EF4444';
                        statusEl.textContent = 'Nenhum rosto detectado. Ajuste a posição e tente novamente.';
                        btn.disabled = false;
                        btn.textContent = '📷 Tentar novamente';
                        return;
                    }

                    faceReferenceDescriptor = detection.descriptor;

                    // Salva no servidor para auditoria (não bloqueia se falhar)
                    try {
                        const snapshot = canvas.toDataURL('image/jpeg', 0.85);
                        await fetch(`${config.apiBase}/sessions/${config.sessionId}/face-self-reference`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': config.csrfToken,
                                'Accept': 'application/json',
                            },
                            body: JSON.stringify({ snapshot }),
                            keepalive: true,
                        });
                    } catch (_) { /* silencioso — não impede a prova */ }

                    statusEl.style.color = '#10B981';
                    statusEl.textContent = 'Rosto registrado com sucesso!';
                    btn.textContent = '✓ Registrado';

                    setTimeout(() => {
                        overlay.remove();
                        resolve();
                    }, 1200);

                } catch (err) {
                    statusEl.style.color = '#EF4444';
                    statusEl.textContent = 'Erro ao acessar câmera. Verifique as permissões.';
                    btn.disabled = false;
                    btn.textContent = '📷 Tentar novamente';
                }
            });
        });
    }

    async function runFaceCheck(type) {
        if (!faceModelsLoaded || !faceReferenceDescriptor) {
            await reportFaceVerification(type, 'skipped', null);
            return;
        }

        if (!webcamStream) {
            await reportFaceVerification(type, 'skipped', null);
            return;
        }

        const video = getWebcamVideoElement();

        try {
            await waitForWebcamVideo(video);
        } catch (_) {
            await reportFaceVerification(type, 'skipped', null);
            return;
        }

        const canvas = faceapi.createCanvasFromMedia(video);

        const allFaces = await faceapi.detectAllFaces(
            canvas,
            new faceapi.TinyFaceDetectorOptions()
        );

        if (allFaces.length === 0) {
            await reportFaceVerification(type, 'no_face', null, canvas);
            return;
        }

        if (allFaces.length > 1) {
            await reportFaceVerification(type, 'multiple_faces', null, canvas);
            return;
        }

        const detection = await faceapi
            .detectSingleFace(canvas, new faceapi.TinyFaceDetectorOptions())
            .withFaceLandmarks(true)
            .withFaceDescriptor();

        if (!detection) {
            await reportFaceVerification(type, 'no_face', null, canvas);
            return;
        }

        const distance   = faceapi.euclideanDistance(faceReferenceDescriptor, detection.descriptor);
        const confidence = parseFloat((1 - distance).toFixed(4));
        const result     = distance < 0.6 ? 'approved' : 'failed';

        await reportFaceVerification(type, result, confidence, result !== 'approved' ? canvas : null);
    }

    async function reportFaceVerification(type, result, confidence, canvas = null) {
        let snapshot = null;

        if (canvas && result !== 'approved' && result !== 'skipped') {
            snapshot = canvas.toDataURL('image/jpeg', 0.7);
        }

        try {
            const response = await fetch(
                `${config.apiBase}/sessions/${config.sessionId}/face-verifications`,
                {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN':  config.csrfToken,
                        'Accept':        'application/json',
                    },
                    body: JSON.stringify({
                        type,
                        result,
                        confidence,
                        snapshot,
                        metadata: {
                            user_agent: navigator.userAgent,
                            timestamp:  Date.now(),
                        },
                    }),
                    keepalive: true,
                }
            );

            if (!response.ok) return;

            const data = await response.json();

            if (data.action === 'warn') {
                showWarning(
                    '⚠️ Verificação de identidade falhou',
                    'Identidade não confirmada repetidamente. O professor foi notificado.'
                );
            } else if (data.action === 'retry') {
                showWarning(
                    '📷 Verificação de identidade',
                    'Posicione seu rosto na câmera para verificação de identidade.'
                );
            }
        } catch (err) {
            console.error('[AvaliaFA Face] Erro ao enviar verificação:', err);
        }
    }

    function stopFaceVerification() {
        if (faceCheckTimer) {
            clearInterval(faceCheckTimer);
            faceCheckTimer = null;
        }
    }

    // -------------------------------------------------------------------------
    // Connection monitoring
    // -------------------------------------------------------------------------

    window.addEventListener('offline', () => {
        emitQueueStatus();
        reportEvent('connection_lost');
    });
    window.addEventListener('online', () => {
        emitQueueStatus();
        reportEvent('connection_restored');
        flushEventQueue();
        flushSnapshotQueue();
    });

    // -------------------------------------------------------------------------
    // UI Helpers
    // -------------------------------------------------------------------------

    function showWarning(title, message) {
        const el = document.getElementById('exam-warning-modal');
        if (el) {
            el.querySelector('[data-title]').textContent   = title;
            el.querySelector('[data-message]').textContent = message;
            el.classList.remove('hidden');
            setTimeout(() => el.classList.add('hidden'), 6000);
        } else {
            const div = document.createElement('div');
            div.style.cssText = [
                'position:fixed', 'top:20px', 'left:50%', 'transform:translateX(-50%)',
                'background:#fef3c7', 'border:2px solid #f59e0b', 'padding:16px 24px',
                'border-radius:12px', 'z-index:99999', 'max-width:480px',
                'font-family:sans-serif', 'box-shadow:0 8px 24px rgba(0,0,0,.15)',
            ].join(';');
            div.innerHTML = `<strong style="display:block">${title}</strong>
                             <p style="margin:6px 0 0;font-size:14px">${message}</p>`;
            document.body.appendChild(div);
            setTimeout(() => div.remove(), 6000);
        }
    }

    function showOverlay(message) {
        const div = document.createElement('div');
        div.style.cssText = [
            'position:fixed', 'inset:0', 'background:rgba(0,0,0,.85)',
            'display:flex', 'align-items:center', 'justify-content:center',
            'z-index:999999', 'font-family:sans-serif',
        ].join(';');
        div.innerHTML = `
            <div style="background:#fff;padding:48px;border-radius:16px;max-width:520px;text-align:center">
                <p style="font-size:1.1rem;white-space:pre-line;line-height:1.7">${message}</p>
            </div>`;
        document.body.appendChild(div);
    }

    // -------------------------------------------------------------------------
    // Init
    // -------------------------------------------------------------------------

    function init() {
        if (initialized) {
            return;
        }

        initialized = true;
        updatePendingCount().then(() => {
            flushEventQueue();
            flushSnapshotQueue();
        });
        ensureFullscreen();
        initWebcam().then(() => initFaceVerification());
        scheduleInactivityCheck();
        sendFingerprint();

        // Verificar monitores no início e a cada 30s
        checkMultipleMonitors();
        monitorCheckTimer = setInterval(checkMultipleMonitors, 30000);
    }

    function waitForSecurityGateRelease() {
        const gate = document.getElementById('security-gate');

        if (!gate) {
            init();
            return;
        }

        window.addEventListener('secureexam:gate-ready', init, { once: true });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', waitForSecurityGateRelease);
    } else {
        waitForSecurityGateRelease();
    }

    // API pública para Blade integration
    window.SecureExamEngine = {
        captureSnapshot,
        reportEvent,
        terminateSession,
        resetInactivity,
        stopFaceVerification,
        runFaceCheck,
        getQueueStatus: () => ({
            pending: eventQueuePendingCount + snapshotQueuePendingCount,
            security_pending: eventQueuePendingCount,
            snapshot_pending: snapshotQueuePendingCount,
            online: navigator.onLine,
        }),
    };

}());
