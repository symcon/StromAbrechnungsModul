<?php

declare(strict_types=1);
include_once __DIR__ . '/stubs/Validator.php';
class StromAbrechnungsModulValidationTest extends TestCaseSymconValidation
{
    public function testValidateStromAbrechnungsModul(): void
    {
        $this->validateLibrary(__DIR__ . '/..');
    }
    public function testValidatePowerBillingModuleModule(): void
    {
        $this->validateModule(__DIR__ . '/../PowerBillingModule');
    }
}