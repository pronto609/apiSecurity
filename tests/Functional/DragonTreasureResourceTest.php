<?php

namespace App\Tests\Functional;

use App\Entity\ApiToken;
use App\Entity\DragonTreasure;
use App\Factory\ApiTokenFactory;
use App\Factory\DragonTreasureFactory;
use App\Factory\UserFactory;
use Zenstruck\Browser\HttpOptions;
use Zenstruck\Foundry\Test\ResetDatabase;

class DragonTreasureResourceTest extends ApiTestCase
{
    use ResetDatabase;

    public function testGetCollectionOfTreasures(): void
    {
        DragonTreasureFactory::createMany(5, [
            'isPublished' => true
        ]);
        DragonTreasureFactory::createMany(1, [
            'isPublished' => false
        ]);

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

    public function testGetOneUnpublishedTreasure404s(): void
    {
        /** @var DragonTreasure $dragonTreasure */
        $dragonTreasure = DragonTreasureFactory::createOne([
            'isPublished' => false
        ]);

        $this->browser()
            ->get('/api/treasures/' . $dragonTreasure->getId())
            ->assertStatus(404)
        ;
    }

    public function testPostCreateTreasure(): void
    {
        $user = UserFactory::createOne();
        $this->browser()
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
//                    'owner' => '/api/users/'. $user->getId()
            ]))
            ->assertStatus(201)
            ->dump()
            ->assertJsonMatches('name', 'A shiny thing')
        ;
    }

    public function testPostToCreateTreasureWithApiKay(): void
    {
        $token = ApiTokenFactory::createOne([
            'scopes' => [ApiToken::SCOPE_TREASURE_CREATE],
        ]);
        $this->browser()
            ->post('/api/treasures', [
                'json' => [],
                'headers' => [
                    'Authorization' => 'Bearer '. $token->getToken()
                ]
            ])
            ->assertStatus(422)
        ;
    }

    public function testPostToCreateTreasureDeniedWithoutScope(): void
    {
        $token = ApiTokenFactory::createOne([
            'scopes' => [ApiToken::SCOPE_TREASURE_EDIT],
        ]);
        $this->browser()
            ->post('/api/treasures', [
                'json' => [],
                'headers' => [
                    'Authorization' => 'Bearer '. $token->getToken()
                ]
            ])
            ->assertStatus(403)
        ;
    }

    public function testPatchToUpdateTreasure(): void
    {
        $user = UserFactory::createOne();
        $treasure = DragonTreasureFactory::createOne([
            'owner' => $user
        ]);

        $this->browser()
            ->actingAs($user)
            ->patch('/api/treasures/'. $treasure->getId(), [
                'json' => [
                    'value' => 12345
                ]
            ])
            ->assertStatus(200)
            ->assertJsonMatches('value', 12345)
            ;

        $user2 = UserFactory::createOne();
        $this->browser()
            ->actingAs($user2)
            ->patch('/api/treasures/'. $treasure->getId(), [
                'json' => [
                    'value' => 6789
                ]
            ])
            ->assertStatus(403)
            ;

        $user2 = UserFactory::createOne();
        $this->browser()
            ->actingAs($user)
            ->patch('/api/treasures/'. $treasure->getId(), [
                'json' => [
                    'owner' => '/api/users/'.$user2->getId()
                ]
            ])
            ->assertStatus(422)
            ;

    }

    public function testPatchUnpublishedWorks(): void
    {
        $user = UserFactory::createOne();
        $treasure = DragonTreasureFactory::createOne([
            'owner' => $user,
            'isPublished' => false
        ]);

        $this->browser()
            ->actingAs($user)
            ->patch('/api/treasures/'. $treasure->getId(), [
                'json' => [
                    'value' => 12345
                ]
            ])
            ->assertStatus(200)
            ->assertJsonMatches('value', 12345)
            ;
    }

    public function testAdminCanPatchToEditTreasure(): void
    {
        $admin = UserFactory::new()->asAdmin()->create();
        $treasure = DragonTreasureFactory::createOne([
            'isPublished' => true
        ]);

        $this->browser()
            ->actingAs($admin)
            ->patch('/api/treasures/'. $treasure->getId(), [
                'json' => [
                    'value' => 12345,
                ]
            ])
            ->assertStatus(200)
            ->assertJsonMatches('value', 12345)
            ->assertJsonMatches('isPublished', true)
            ;
    }

    public function testOwnerCanSeeIsPublishedAndIsMineFields(): void
    {
        $user = UserFactory::createOne();
        $treasure = DragonTreasureFactory::createOne([
            'isPublished' => true,
            'owner' => $user
        ]);

        $this->browser()
            ->actingAs($user)
            ->patch('/api/treasures/'. $treasure->getId(), [
                'json' => [
                    'value' => 12345,
                ]
            ])
            ->assertStatus(200)
            ->assertJsonMatches('value', 12345)
            ->assertJsonMatches('isPublished', true)
            ->assertJsonMatches('isMine', true)
        ;
    }
}