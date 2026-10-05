<?php

declare(strict_types=1);

namespace Tests\Feature;

use CodeIgniter\I18n\Time;
use Modules\Documents\Dashboard\DocumentCards;
use Modules\Documents\Deadlines\DocumentDeadlines;
use Modules\Documents\Models\DocumentModel;
use Tests\Support\FamilyTestCase;

/**
 * @internal
 */
final class DocumentsTest extends FamilyTestCase
{
    public function testCreateDocumentWithoutFilesStoresMeta(): void
    {
        $this->loginAs('adult');
        $personId = $this->makePerson('Ema', 'child');

        $result = $this->withSession()->post('dokumenty', $this->csrf() + [
            'title' => 'Kartička Ema', 'kind' => 'karticka', 'person_id' => $personId,
            'meta_insurer' => 'Dôvera', 'meta_number' => '1234567890', 'expires_at' => '2031-12-31',
        ]);
        $result->assertRedirect();

        $document = model(DocumentModel::class)->first();
        $this->assertSame('Dôvera', $document->metaValue('insurer'));
        $this->assertSame('1234567890', $document->metaValue('number'));
        $this->assertTrue($document->isCard());

        $result = $this->get('dokumenty/karticky');
        $result->assertStatus(200);
        $result->assertSee('1234567890');
        $result->assertSee('Ema');
    }

    public function testExpiringDocumentsBecomeDeadlinesAndCards(): void
    {
        $user  = $this->loginAs('adult');
        $today = Time::parse('2030-01-10');
        $docs  = model(DocumentModel::class);
        $docs->insert(['household_id' => $this->householdId, 'title' => 'Pas Vlado', 'kind' => 'doklad', 'expires_at' => '2030-01-20']);
        $docs->insert(['household_id' => $this->householdId, 'title' => 'Záruka práčka', 'kind' => 'zarucny_list', 'expires_at' => '2029-12-01']);
        $docs->insert(['household_id' => $this->householdId, 'title' => 'Bez dátumu', 'kind' => 'iny']);

        $deadlines = (new DocumentDeadlines())->deadlines($this->householdId, $today, $today->addDays(30));
        $this->assertCount(1, $deadlines);
        $this->assertSame('document', $deadlines[0]->kind);
        $this->assertSame(10, $deadlines[0]->daysLeft($today));

        $cards = (new DocumentCards())->dashboardCards($user, $today);
        $this->assertCount(2, $cards);
        $this->assertSame('danger', $cards[0]->urgency);
        $this->assertStringContainsString('Záruka práčka', $cards[0]->title);
        $this->assertSame('warning', $cards[1]->urgency);
    }

    public function testFileOfOtherHouseholdIsNotServed(): void
    {
        $this->loginAs('adult');
        $otherHousehold = (int) model(\Modules\Household\Models\HouseholdModel::class)->insert(['name' => 'Susedia']);
        $id             = model(DocumentModel::class)->insert(['household_id' => $otherHousehold, 'title' => 'Cudzie', 'kind' => 'iny']);

        $this->expectException(\CodeIgniter\Exceptions\PageNotFoundException::class);
        $this->get("dokumenty/{$id}");
    }
}
