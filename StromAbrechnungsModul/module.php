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
        //$this->RegisterPropertyInteger("AverageBase", 30);

        //Profiles
        if (!IPS_VariableProfileExists('EuroRating')) {
            IPS_CreateVariableProfile('EuroRating', 2);
            IPS_SetVariableProfileIcon('EuroRating', 'Euro');
            IPS_SetVariableProfileValues('EuroRating', 0, 0, 0);
            IPS_SetVariableProfileText('EuroRating', '', '€');
            IPS_SetVariableProfileDigits('EuroRating', 2);
            IPS_SetVariableProfileAssociation('EuroRating', -9999999, '%.2f', '', 0xFF0000);
            IPS_SetVariableProfileAssociation('EuroRating', 0, '%.2f', '', 0x00FF00);
        }

        //Variables
        $this->RegisterVariableInteger('DaysUntil', $this->Translate('days until next reading'), '', 0);
        $this->RegisterVariableFloat('PlannedConsumption', $this->Translate('planned consumption/day'), '~Electricity', 2);
        $this->RegisterVariableFloat('MeterTarget', $this->Translate('meter reading (target)'), '~Electricity', 1);
        $this->RegisterVariableFloat('Difference', $this->Translate('credit note/back payment'), 'EuroRating', 4);
        $this->RegisterVariableFloat('AverageConsumption', $this->Translate('average consumption of the last 30 days'), '~Electricity', 3);
        $this->RegisterVariableFloat('PowerPrice', $this->Translate('power price'), '~Euro', -1);
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

        $archiveControlID = IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0];

        if (@IPS_VariableExists($this->ReadPropertyInteger('Source')) && AC_GetAggregationType($archiveControlID, $this->ReadPropertyInteger('Source')) == 1) {
            $this->RegisterMessage($this->ReadPropertyInteger('Source'), VM_UPDATE);
        }

        $this->UpdateCalculations();
    }

    private function GetAverageConsumption()
    {
        $archiveControlID = IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0];

        $loggedValues = AC_GetAggregatedValues($archiveControlID, $this->ReadPropertyInteger('Source'), 1, strtotime('-30 Days'), time(), 0);

        $sum = 0;

        foreach ($loggedValues as $loggedValue) {
            $sum += $loggedValue['Avg'];
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

    public function MessageSink($TimeStamp, $SenderID, $Message, $Data)
    {
        //Triggered on variable update
        $this->UpdateCalculations();
    }

    public function UpdateCalculations()
    {
        $archiveControlID = IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0];
        
        $this->SendDebug("last", date("d.m.Y", $this->GetReadingDays()['last']), 0);
        $this->SendDebug("next", date("d.m.Y", $this->GetReadingDays()['next']), 0);
        $this->SendDebug("difference", $this->GetDaysToReading(), 0);
        
        
        if (@IPS_VariableExists($this->ReadPropertyInteger('Source')) && AC_GetAggregationType($archiveControlID, $this->ReadPropertyInteger('Source')) == 1) {
            if ($this->GetDaysToReading() != 0) {
                $this->SetStatus(102);
                $powerPrice = (($this->ReadPropertyFloat('BasePrice') / $this->ReadPropertyInteger('PlannedConsumptionYear')) + $this->ReadPropertyFloat('LaborPrice')) / 100;
                SetValue($this->GetIDForIdent('PowerPrice'), $powerPrice);

                SetValue($this->GetIDForIdent('DaysUntil'), $this->GetDaysToReading());
                SetValue($this->GetIDForIdent('PlannedConsumption'), $this->ReadPropertyInteger('PlannedConsumptionYear') / $this->GetDaysToReading());

                $meterTarget = GetValue($this->GetIDForIdent('PlannedConsumption')) * GetValue($this->GetIDForIdent('DaysUntil')) + $this->ReadPropertyInteger('LastMeterReading');
                SetValue($this->GetIDForIdent('MeterTarget'), $meterTarget);

                SetValue($this->GetIDForIdent('Difference'), (($meterTarget - GetValue($this->ReadPropertyInteger('Source'))) * $powerPrice));
                SetValue($this->GetIDForIdent('AverageConsumption'), $this->GetAverageConsumption());
            } else {
                $this->SetStatus(104);
                SetValue($this->GetIDForIdent('DaysUntil'), 0);
            }
            
        } else {
            $this->SetStatus(200);
        }
    }
}