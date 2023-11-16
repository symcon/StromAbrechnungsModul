<?php

declare(strict_types=1);
class StromAbrechnungsModul extends IPSModule
{
    public function Create()
    {
        //Never delete this line!
        parent::Create();

        //Proprties
        $this->RegisterPropertyInteger('Source', 0);
        $this->RegisterPropertyFloat('BasePrice', 71.76);
        $this->RegisterPropertyFloat('LaborPrice', 22.57);
        $this->RegisterPropertyString('ReadingDate', '{"year":2019,"month":1,"day":1}');
        $this->RegisterPropertyInteger('LastMeterReading', 70518);
        $this->RegisterPropertyInteger('PlannedConsumptionYear', 4250);

        //Profiles
        if (!IPS_VariableProfileExists('SAM.EuroRating')) {
            IPS_CreateVariableProfile('SAM.EuroRating', 2);
            IPS_SetVariableProfileIcon('SAM.EuroRating', 'Euro');
            IPS_SetVariableProfileValues('SAM.EuroRating', 0, 0, 0);
            IPS_SetVariableProfileText('SAM.EuroRating', '', ' ' . '€');
            IPS_SetVariableProfileDigits('SAM.EuroRating', 2);
            IPS_SetVariableProfileAssociation('SAM.EuroRating', -9999999, '%.2f', '', 0xFF0000);
            IPS_SetVariableProfileAssociation('SAM.EuroRating', 0, '%.2f', '', 0x00FF00);
        }
        if (!IPS_VariableProfileExists('SAM.Calendar')) {
            IPS_CreateVariableProfile('SAM.Calendar', 1);
            IPS_SetVariableProfileIcon('SAM.Calendar', 'Calendar');
            IPS_SetVariableProfileText('SAM.Calendar', '', ' ' . $this->Translate('days'));
        }
        if (!IPS_VariableProfileExists('SAM.PowerPrice')) {
            IPS_CreateVariableProfile('SAM.PowerPrice', 2);
            IPS_SetVariableProfileIcon('SAM.PowerPrice', 'Euro');
            IPS_SetVariableProfileText('SAM.PowerPrice', '', ' ' . 'ct/kwh');
            IPS_SetVariableProfileDigits('SAM.PowerPrice', 2);
        }

        //Variables
        $this->RegisterVariableInteger('DaysUntil', $this->Translate('days until next reading'), 'SAM.Calendar', 0);
        $this->RegisterVariableFloat('PlannedConsumption', $this->Translate('planned consumption/day'), '~Electricity', 3);
        $this->RegisterVariableFloat('MeterTarget', $this->Translate('meter reading (target)'), '~Electricity', 1);
        $this->RegisterVariableFloat('DifferencePayment', $this->Translate('credit note/back payment'), 'SAM.EuroRating', 6);
        $this->RegisterVariableFloat('AverageConsumption', $this->Translate('average consumption of the last 30 days'), '~Electricity', 4);
        $this->RegisterVariableFloat('PowerPrice', $this->Translate('power price'), 'SAM.PowerPrice', -1);
        $this->RegisterVariableInteger('DaysSinceReading', $this->Translate('Days since last reading'), 'SAM.Calendar', 1);
        $this->RegisterVariableFloat('Difference', $this->Translate('Variance'), '~Electricity', 5);
    }

    public function Destroy()
    {
        //Never delete this line!
        parent::Destroy();
    }

    public function ApplyChanges()
    {
        //Never delete this line!
        parent::ApplyChanges();

        //Deleting all references in order to readd them
        foreach ($this->GetReferenceList() as $referenceID) {
            $this->UnregisterReference($referenceID);
        }
        $archiveControlID = IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0];
        if (@IPS_VariableExists($this->ReadPropertyInteger('Source')) && AC_GetAggregationType($archiveControlID, $this->ReadPropertyInteger('Source')) == 1) {
            $this->RegisterMessage($this->ReadPropertyInteger('Source'), VM_UPDATE);
            $this->RegisterReference($this->ReadPropertyInteger('Source'));
        }

        $this->UpdateCalculations();
    }

    public function MessageSink($TimeStamp, $SenderID, $Message, $Data)
    {
        //Triggered on variable update
        $this->UpdateCalculations();
    }

    public function UpdateCalculations()
    {
        $archiveControlID = IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0];
        $sourceVariableID = $this->ReadPropertyInteger('Source');

        if (!IPS_VariableExists($sourceVariableID)) {
            $this->SetStatus(200); //Variable doesn't exist
        } elseif (IPS_GetVariable($sourceVariableID)['VariableType'] == VARIABLETYPE_BOOLEAN || IPS_GetVariable($sourceVariableID)['VariableType'] == VARIABLETYPE_STRING) {
            $this->SetStatus(201); //Variable is not numeric
        } elseif (!AC_GetLoggingStatus($archiveControlID, $sourceVariableID)) {
            $this->SetStatus(202); //Variable not logged
        } elseif (!AC_GetAggregationType($archiveControlID, $sourceVariableID)) {
            $this->SetStatus(203); //Variable not aggregated as counter
        } else {
            if ($this->GetDaysToReading() != 0) {
                $this->SetStatus(102);
                $powerPrice = (($this->ReadPropertyFloat('BasePrice') / $this->ReadPropertyInteger('PlannedConsumptionYear')) + ($this->ReadPropertyFloat('LaborPrice')) / 100);
                SetValue($this->GetIDForIdent('PowerPrice'), $powerPrice * 100);

                SetValue($this->GetIDForIdent('DaysUntil'), $this->GetDaysToReading());
                SetValue($this->GetIDForIdent('PlannedConsumption'), $this->ReadPropertyInteger('PlannedConsumptionYear') / $this->GetReadingDiff());
                SetValue($this->GetIDForIdent('DaysSinceReading'), $this->GetReadingDiff() - $this->GetDaysToReading());

                $meterTarget = GetValue($this->GetIDForIdent('PlannedConsumption')) * GetValue($this->GetIDForIdent('DaysSinceReading')) + $this->ReadPropertyInteger('LastMeterReading');
                SetValue($this->GetIDForIdent('MeterTarget'), $meterTarget);

                $priceDiff = (($meterTarget - GetValue($sourceVariableID)) * $powerPrice);
                SetValue($this->GetIDForIdent('DifferencePayment'), $priceDiff);
                SetValue($this->GetIDForIdent('Difference'), $meterTarget - GetValue($sourceVariableID));
                SetValue($this->GetIDForIdent('AverageConsumption'), $this->GetAverageConsumption());

                $this->SendDebug('PriceDiff', $priceDiff, 0);
                $this->SendDebug('DaysUntil', GetValue($this->GetIDForIdent('DaysUntil')), 0);
                $this->SendDebug('ReadingDiff', $this->GetReadingDiff(), 0);
                $this->SendDebug('DaysToReading', $this->GetDaysToReading(), 0);
            } else {
                $this->SetStatus(104);
                SetValue($this->GetIDForIdent('DaysUntil'), 0);
            }
        }
    }

    private function GetAverageConsumption()
    {
        $archiveControlID = IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0];

        $loggedValues = AC_GetAggregatedValues($archiveControlID, $this->ReadPropertyInteger('Source'), 1, strtotime('-30 Days'), time(), 0);

        $sum = 0;

        foreach ($loggedValues as $loggedValue) {
            $sum += $loggedValue['Avg'];
        }
        if (count($loggedValues) == 0) {
            return 0;
        }
        return $sum / count($loggedValues);
    }

    private function GetDaysToReading()
    {
        $difference = (time() - $this->GetReadingDays()['next']) / 60 / 60 / 24;
        return floor($difference) * -1;
    }

    private function GetReadingDays()
    {
        $readingDate = json_decode($this->ReadPropertyString('ReadingDate'), true);
        $lastReadingDay = mktime(0, 0, 0, $readingDate['month'], $readingDate['day'], $readingDate['year']);
        $nextReadingDay = strtotime('+1 year', $lastReadingDay);

        $readingDates = [
            'last' => $lastReadingDay,
            'next' => $nextReadingDay
        ];

        return $readingDates;
    }

    private function GetReadingDiff()
    {
        $diff = ($this->GetReadingDays()['next'] - $this->GetReadingDays()['last']) / 60 / 60 / 24;
        return $diff;
    }
}