<?php

namespace Tests\Unit;

use App\Models\Contract;
use Tests\TestCase;

class ContractModelTest extends TestCase
{
    public function test_is_signed_returns_true_when_contract_is_signed(): void
    {
        $contract = new Contract;
        $contract->signed_at = now();

        $this->assertTrue($contract->isSigned());
    }

    public function test_is_signed_returns_false_when_contract_is_not_signed(): void
    {
        $contract = new Contract;
        $contract->signed_at = null;

        $this->assertFalse($contract->isSigned());
    }
}
