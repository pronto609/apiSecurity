<?php

namespace App\Tests\Functional;

use App\Factory\DragonTreasureFactory;
use App\Factory\UserFactory;
use Zenstruck\Browser\HttpOptions;
use Zenstruck\Foundry\Test\ResetDatabase;

class DragonTreasureResourceTest extends ApiTestCase
{
    use ResetDatabase;

    public function testGetCollectionOfTreasures(): void
    {
        DragonTreasureFactory::createMany(5);

        $json = $this->browser()
            ->get('/api/treasures')
            ->assertJson()
            ->assertJsonMatches('"hydra:totalItems"', 5)
            ->json()
        ;

        $json->assertMatches('keys("hydra:member"[0])', [
            "@id",
            "@type",
            "name",
            "description",
            "value",
            "coolFactor",
            "owner",
            "shortDescription",
            "plunderedAtAgo"
        ]);
    }

    public function testPostCreateTreasure(): void
    {
        $user = UserFactory::createOne();
        $this->browser()
//            ->post('/login', [
//                'json' => [
//                    'email' => $user->getEmail(),
//                    'password' => 'pass'
//                ]
//            ])
                ->actingAs($user)
            ->post('/api/treasures', [
                'json' => []
            ])
            ->assertStatus(422)
            ->post('/api/treasures', HttpOptions::json([
                    'name' => 'A shiny thing',
                    'description' => 'Test description',
                    'value' => 1000,
                    'coolFactor' => 5,
                    'owner' => '/api/users/'. $user->getId()
            ]))
            ->assertStatus(201)
            ->dump()
            ->assertJsonMatches('name', 'A shiny thing')
        ;
    }
}