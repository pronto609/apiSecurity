<?php

namespace App\Tests\Functional;

use App\Factory\DragonTreasureFactory;
use App\Factory\UserFactory;
use Zenstruck\Foundry\Test\ResetDatabase;

class UserResourceTest extends ApiTestCase
{
    private const USER_BASE_URL = '/api/users';
    use ResetDatabase;

    public function testPostToCreateUser(): void
    {
        $this->browser()
            ->post(self::USER_BASE_URL, [
                'json' => [
                    'email' => 'dragon_in_the_morning@coffe.com',
                    'username' => 'dragon_in_the_morning',
                    'password' => 'tada'
                ]
        ])
        ->assertStatus(201)
            ->post('/login',[
                'json' => [
                    'email' => 'dragon_in_the_morning@coffe.com',
                    'password' => 'tada'
                ]
            ])
            ->assertSuccessful()
        ;
    }

    public function testPatchToUpdateUser(): void
    {
        $user = UserFactory::createOne();

        $this->browser()
            ->actingAs($user)
            ->patch(self::USER_BASE_URL.'/'.$user->getId(), [
                'json' => [
                    'username' => 'changed',
                ],
                'headers' => [
                    'Content-Type' => 'application/merge-patch+json'
                ]
        ])
        ->assertStatus(200)
        ;
    }

    public function testTreasuresCannotBeStolen(): void
    {
        $user = UserFactory::createOne();
        $otherUser = UserFactory::createOne();
        $dragonTreasure = DragonTreasureFactory::createOne([
            'owner' => $otherUser
        ]);

        $this->browser()
            ->actingAs($user)
            ->patch(self::USER_BASE_URL.'/'.$user->getId(), [
                'json' => [
                    'username' => 'changed',
                    'dragonTreasures' => [
                        '/api/treasures/'.$dragonTreasure->getId()
                    ]
                ],
                'headers' => [
                    'Content-Type' => 'application/merge-patch+json'
                ]
        ])
        ->assertStatus(422)
        ;
    }


}