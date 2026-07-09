<?php

namespace Webkul\RestApi\Tests\Integration;

/**
 * Lead endpoints against a live Krayin instance, focused on the error contracts
 * this fork hardened: a missing lead id on show/update/destroy is a JSON 404
 * (not a 500 after partially writing data), invalid input is a 422, and
 * mass-destroy handles unknown ids gracefully instead of crashing.
 */
class LeadsIntegrationTest extends IntegrationTestCase
{
    private const MISSING_ID = 999999999;

    public function test_list_leads_returns_paginated_json(): void
    {
        $response = $this->get('api/v1/leads');

        $this->assertSame(200, $response['status']);
        $this->assertArrayHasKey('data', $response['json']);
        $this->assertArrayHasKey('meta', $response['json']);
    }

    public function test_show_nonexistent_lead_returns_404_json(): void
    {
        $response = $this->get('api/v1/leads/'.self::MISSING_ID);

        $this->assertSame(404, $response['status']);
        $this->assertHumanMessage($response['json']);
    }

    public function test_update_nonexistent_lead_returns_404_json(): void
    {
        $response = $this->put('api/v1/leads/'.self::MISSING_ID, ['title' => 'nope']);

        $this->assertSame(404, $response['status']);
        $this->assertHumanMessage($response['json']);
    }

    public function test_delete_nonexistent_lead_returns_404_json(): void
    {
        $response = $this->delete('api/v1/leads/'.self::MISSING_ID);

        $this->assertSame(404, $response['status']);
        $this->assertHumanMessage($response['json']);
    }

    /**
     * Creating a lead against a pipeline stage that does not exist must surface
     * a clean JSON 404 (the stage lookup uses findOrFail), NOT a 500 after
     * partial data has been written — the lead-endpoint hardening in this fork.
     */
    public function test_create_lead_with_unknown_stage_returns_404(): void
    {
        $response = $this->post('api/v1/leads', [
            'title'                  => $this->unique('Lead '),
            'lead_pipeline_stage_id' => self::MISSING_ID,
        ]);

        $this->assertSame(404, $response['status']);
        $this->assertHumanMessage($response['json']);
    }

    /**
     * mass-destroy with only unknown ids must not blow up with a 500; it should
     * answer with a clean JSON status (the fork reports the real affected
     * count rather than crashing on a partially-valid set).
     */
    public function test_mass_destroy_with_unknown_ids_is_handled_gracefully(): void
    {
        $response = $this->request('POST', 'api/v1/leads/mass-destroy', [
            'indices' => [self::MISSING_ID, self::MISSING_ID - 1],
        ]);

        $this->assertNotSame(500, $response['status'], 'mass-destroy must not 500 on unknown ids.');
        $this->assertLessThan(500, $response['status']);
        $this->assertHumanMessage($response['json']);
    }
}
