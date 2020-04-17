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

- Unter "Instanz hinzufügen" kann das 'StromAbrechnung'-Modul mithilfe des Schnellfilters gefunden werden.
    - Weitere Informationen zum Hinzufügen von Instanzen in der [Dokumentation der Instanzen](https://www.symcon.de/service/dokumentation/konzepte/instanzen/#Instanz_hinzufügen)

__Konfigurationsseite__:

Name                | Beschreibung
------------------- | ---------------------------------
Quelle              | Die Variable des Hauptzählers, welche als Zähler geloggt wird
Grundpreis          | Der Grundpreis
Arbeitspreis        | Der Arbeitspreis
Ablesedatum         | Datum der letzen Zählerstandsablesung 
letzter Zählerstand | Der Zählerstand am lezten Ablesetermin
geplanter Verbrauch | Der geplante Stromverbrauch/Jahr


### 5. Statusvariablen und Profile

Die Statusvariablen/Kategorien werden automatisch angelegt. Das Löschen einzelner kann zu Fehlfunktionen führen.

##### Statusvariablen

Name                                            | Typ     | Beschreibung
----------------------------------------------- | ------- | -------------------------------
Energiepreis                                    | Float   | Energiepreis mit einbezug des Grundpreises
Tage bis zur nächsten Ablesung                  | Integer | Tage bis zur nächsten Ablesung (1 Jahr ausgehend von der letzten Ablesung)
Tage seit letzter Ablesung                      | Integer | Tage seit letzter Ablesung
Zählerstand(Soll)                               | Float   | Der Soll-Wert des heutigen Zählerstandes basierend auf dem geplanten Verbrauch
Geplanter Verbrauch/Tag                         | Float   | Der Geplante Verbrauch pro Tag basierend auf dem geplanten Jahresverbrauch
durchschnittlicher Verbrauch der letzten 30 Tage| Float   | Der Mittelwert des Verbrauches der letzten 30 Tage
Abweichung                                      | Float   | Die Abweichung des tatsächlichen Zählerstandes zum Soll-Wert
Gutschrift/Rückzahlung                          | Float   | Menge der Rückzahlung bzw. Gutschrift berechnet durch die Abweichung

##### Profile:

Name           | Typ
-------------  | ------- 
SAM.EuroRating | Float
SAM.PowerPrice | Float
SAM.Calendar   | Integer

### 6. WebFront

Hier werden alle wichtigen errechneten Werte angezeigt. 

### 7. PHP-Befehlsreferenz

`boolean SAM_UpdateCalculations(integer $InstanzID);`  
Berechnet alle im Webfront angezeigten Werte basierend auf den Daten auf der Konfigurationsseite.  
Die Funktion liefert keinerlei Rückgabewert.  
Beispiel:  
`SAM_UpdateCalculations(12345);`
