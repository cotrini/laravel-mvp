<?php

namespace Tests\Feature;

use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class PhoneCompletionTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_phone_completion_flag_blocks_access_until_phone_is_saved(): void
    {
        $user = User::factory()->create(['phone_number' => null]);

        $this->actingAs($user)
            ->withSession(['phone_completion_required' => true])
            ->get(route('properties.index'))
            ->assertRedirect(route('profile.phone.show'));

        $this->get(route('profile.phone.show'))
            ->assertOk()
            ->assertSee('Este paso es obligatorio')
            ->assertSee('name="phone_number"', false);
    }

    public function test_user_can_complete_phone_and_continue(): void
    {
        $user = User::factory()->create(['phone_number' => null]);

        $this->actingAs($user)
            ->withSession(['phone_completion_required' => true])
            ->post(route('profile.phone.store'), [
                'phone_number' => '+57 300 123 4567',
            ])
            ->assertRedirect(route('dashboard.index'))
            ->assertSessionMissing('phone_completion_required');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'phone_number' => '+573001234567',
        ]);
    }

    public function test_phone_completion_rejects_a_number_already_used_by_another_user(): void
    {
        User::factory()->create(['phone_number' => '+573001234567']);
        $user = User::factory()->create(['phone_number' => null]);

        $this->actingAs($user)
            ->withSession(['phone_completion_required' => true])
            ->from(route('profile.phone.show'))
            ->post(route('profile.phone.store'), [
                'phone_number' => '300 123 4567',
            ])
            ->assertRedirect(route('profile.phone.show'))
            ->assertSessionHasErrors('phone_number');
    }
}
