# SPEC.md — Digital Watch Landing Page

## 1. Concept & Vision

A premium, conversion-focused landing page for selling digital watches. The design evokes luxury and precision engineering with a dark, sophisticated aesthetic reminiscent of high-end watch brands. The page should feel exclusive, modern, and trustworthy — conveying that these aren't ordinary digital watches but crafted timepieces with advanced technology.

## 2. Design Language

### Aesthetic Direction
Dark luxury with gold accents — inspired by premium watch brands like Omega and Tag Heuer, but with a modern digital edge.

### Color Palette
| Role | Color | Hex |
|------|-------|-----|
| Background Primary | Deep Black | `#0a0a0a` |
| Background Secondary | Charcoal | `#1a1a1a` |
| Background Card | Dark Gray | `#252525` |
| Primary Accent | Gold | `#d4a574` |
| Secondary Accent | Amber | `#c9956c` |
| Text Primary | White | `#ffffff` |
| Text Secondary | Light Gray | `#9ca3af` |
| Success | Emerald | `#10b981` |

### Typography
- **Headings**: Playfair Display (serif) — elegant, premium feel
- **Body**: Inter (sans-serif) — clean, modern readability
- **Accent/CTA**: Inter Semi-Bold

### Spatial System
- Container max-width: 1280px
- Section padding: 80px vertical (desktop), 48px (mobile)
- Card padding: 32px
- Border radius: 16px (cards), 8px (buttons)

### Motion Philosophy
- Smooth scroll between sections
- Fade-in animations on scroll (AOS-style)
- Hover scale (1.02) on product cards
- Button hover: subtle glow effect
- Transition duration: 300ms ease

## 3. Layout & Structure

### Page Sections (Top to Bottom)
1. **Hero Section** — Full viewport, background gradient, main headline, CTA button
2. **Features Section** — 3-column grid showcasing key watch features
3. **Product Showcase** — Horizontal scrolling gallery of watch models
4. **Tech Specs** — Accordion-style expandable specifications
5. **Social Proof** — Customer testimonials carousel
6. **CTA Section** — Final conversion push with pricing
7. **Footer** — Minimal footer with links

### Responsive Strategy
- Desktop: Full layout with horizontal arrangements
- Tablet: 2-column grids
- Mobile: Single column, adjusted spacing

## 4. Features & Interactions

### Core Features
- Hero with animated background particles
- Feature cards with icons and hover effects
- Product gallery with filter buttons (All/Sport/Classic/Luxury)
- Tech specs accordion (click to expand/collapse)
- Testimonials carousel with auto-rotate
- Sticky header on scroll
- Smooth scroll navigation

### Interaction Details
- **Navigation**: Click scrolls to section, active state highlights current section
- **Product Cards**: Hover reveals "View Details" overlay
- **Filter Buttons**: Active state with gold underline
- **Testimonials**: Auto-rotate every 5s, pause on hover, manual arrows
- **CTA Button**: Gold gradient, pulse animation on idle

### Edge Cases
- Images: Placeholder gradient backgrounds if images fail to load
- Testimonials: Minimum 3 items, maximum 10

## 5. Component Inventory

### Navigation Bar
- Logo (text-based: "CHRONOS")
- Nav links: Features, Products, Specs, Reviews
- CTA button: "Buy Now"
- States: Default (transparent), Scrolled (solid background)

### Hero Section
- H1 headline with gradient text effect
- Subheadline paragraph
- Primary CTA button
- Background: Radial gradient with subtle pattern

### Feature Card
- Icon (SVG, 48px)
- Title (h3)
- Description paragraph
- States: Default, Hover (slight lift + shadow)

### Product Card
- Watch image placeholder (gradient)
- Model name
- Price
- Quick features list
- States: Default, Hover (scale + overlay)

### Testimonial Card
- Quote text
- Customer name
- Rating stars (5 gold stars)
- Avatar placeholder (gradient circle)

### Tech Spec Item
- Label (clickable header)
- Expandable content panel
- States: Collapsed, Expanded (with rotate arrow icon)

### CTA Section
- Headline
- Price display (original + discounted)
- Primary CTA button
- Trust badges row

### Footer
- Logo
- Navigation links
- Social media icons
- Copyright

## 6. Technical Approach

### Framework
Single HTML file with embedded CSS (TailwindCSS via CDN) and Vanilla JavaScript. No build step required.

### External Dependencies
- TailwindCSS CDN (v3.4)
- Google Fonts (Playfair Display, Inter)
- Heroicons (via inline SVG)

### Architecture
- Single `index.html` file
- Inline `<style>` for custom CSS
- Inline `<script>` for interactions
- No external JS dependencies
