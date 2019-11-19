# StromAbrechnungsModul
Das Modul liefert eine den Kosten-Aufstellung ähnlich der Jahresabrechnung vom Energieversorger. 


### Inhaltsverzeichnis

1. [Funktionsumfang](#1-funktionsumfang)
2. [Voraussetzungen](#2-voraussetzungen)
3. [Software-Installation](#3-software-installation)
4. [Einrichten der Instanzen in IP-Symcon](#4-einrichten-der-instanzen-in-ip-symcon)
5. [Statusvariablen und Profile](#5-statusvariablen-und-profile)
6. [WebFront](#6-webfront)
7. [PHP-Befehlsreferenz](#7-php-befehlsreferenz)

### 1. Funktionsumfang

* Erzeugt eine Kosten-Aufstellung mithilfe einiger Eckdaten.

### 2. Voraussetzungen

- IP-Symcon ab Version 5.0

### 3. Software-Installation

* Über den Module Store das Modul Strom-Abrechnungs-Modul installieren.
* Alternativ über das Module Control folgende URL hinzufügen:
`https://github.com/symcon/StromAbrechnungsModul`  

### 4. Einrichten der Instanzen in IP-Symcon

- Unter "Instanz hinzufügen" ist das 'StromAbrechnung'-Modul unter dem Hersteller '(Gerät)' aufgeführt.  

__Konfigurationsseite__:

Name                | Beschreibung
------------------- | ---------------------------------
Quelle              | Die Variable des Hauptzählers
Grundpreis          | Der Grundpreis
Arbeitspreis        | Der Arbeitspreis
Ablresedatum        | Datum der letzen Zählerstandsablesung 
letzter Zählerstand | Der Zählerstand 
geplanter Verbrauch | Der geplante Stromverbrauch


### 5. Statusvariablen und Profile

Die Statusvariablen/Kategorien werden automatisch angelegt. Das Löschen einzelner kann zu Fehlfunktionen führen.

##### Statusvariablen

Name                                            | Typ     | Beschreibung
----------------------------------------------- | ------- | -------------------------------
Energiepreis                                    | Float   | Energiepreis mit einbezug des Grundpreises
Tage bis zur nächsten Ablesung                  | Integer | Tage bis zur nächsten Ablesung (1 Jahr ausgehend von der letzten Ablesung)
Zählerstand(Soll)                               | Float   | Der Soll-Wert des Zählers
Geplanter Verbrauch/Tag                         | Float   | Der Geplante Verbrauch pro Tag basierend auf dem geplanten Jahresverbrauch
durchschnittlicher Verbrauch der letzten 30 Tage| Float   | Der Mittelwert des Verbrauches der letzten 30 Tage
Gutschrift/Rückzahlung                          | Float   | Menge der Rückzahlung bzw. Gutschrift

##### Profile:

Name          | Typ
------------- | ------- 
SAM.EuroRating | Float

### 6. WebFront

Hier werden alle wichtigen errechneten Werte angezeigt. 

### 7. PHP-Befehlsreferenz

`boolean SAM_UpdateCalculations(integer $InstanzID);`  
Berechnet alle im Webfront angezeigten Werte basierend auf den Daten auf der Konfigurationsseite.  
Die Funktion liefert keinerlei Rückgabewert.  
Beispiel:  
`SAM_UpdateCalculations(12345);`
