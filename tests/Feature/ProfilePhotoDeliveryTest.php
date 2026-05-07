<?php

namespace Tests\Feature;

use App\Models\ClientSystem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfilePhotoDeliveryTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_photo_url_uses_application_route_for_legacy_public_paths(): void
    {
        Storage::fake('public');

        $system = ClientSystem::query()->create([
            'name' => 'Sistema Foto',
            'slug' => 'sistema-foto',
            'active' => true,
        ]);

        $user = User::factory()->create([
            'client_system_id' => $system->id,
            'role' => 'student',
            'profile_photo_path' => "public/profile-photos/{$system->id}/photo.jpg",
        ]);

        Storage::disk('public')->putFileAs(
            "profile-photos/{$system->id}",
            UploadedFile::fake()->image('photo.jpg'),
            'photo.jpg'
        );

        $user->forceFill([
            'profile_photo_path' => "public/profile-photos/{$user->id}/photo.jpg",
        ])->save();

        Storage::disk('public')->putFileAs(
            "profile-photos/{$user->id}",
            UploadedFile::fake()->image('photo.jpg'),
            'photo.jpg'
        );

        $freshUser = $user->fresh();

        $this->assertTrue($freshUser->hasProfilePhoto());
        $this->assertSame(route('media.profile-photo', $freshUser), $freshUser->profilePhotoUrl());

        $this->get(route('media.profile-photo', $freshUser))
            ->assertOk();
    }
}
