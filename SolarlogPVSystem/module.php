<?
//  Modul zum Darstellen von Solaranlagen mit allen Solarlog Daten
//  
//  Version 0.9
//
// ************************************************************
class SolarlogPVSystem extends IPSModule {
	public function Create() {
		// Diese Zeile nicht löschen.
		parent::Create(); 
		$archiv = IPS_GetInstanceIDByName("Archiv", 0 );
		// Verbraucher, Erzeuger und Batteriedaten konfigurieren
		$this->RegisterPropertyInteger("Archiv",$archiv);
		$this->RegisterPropertyInteger("Update",1);
		$this->RegisterPropertyInteger("LeistungGesamt", 0);
		$this->RegisterPropertyInteger("LeistungWR1", 0);
		$this->RegisterPropertyInteger("LeistungWR2", 0);
		$this->RegisterPropertyInteger("LeistungWR3", 0);
		$this->RegisterPropertyString("Inbetriebnahme", "");
		$this->RegisterPropertyString("Standort", "");
		$this->RegisterPropertyInteger("Ausrichtung", 0);
		$this->RegisterPropertyInteger("Neigung", 0);
		$this->RegisterPropertyInteger("SollErtrag", 0);
				
		$this->RegisterPropertyString("ServerAdresse", "");
		$this->RegisterPropertyString("Username", "");
		$this->RegisterPropertyString("Password", "");
		$this->RegisterPropertyString("RemoteDir", "");
		$this->RegisterPropertyString("LocalDir", "");
		
		$this->RegisterPropertyString("WR1Pac", "");
		$this->RegisterPropertyString("WR1DaySum", "");
		$this->RegisterPropertyString("WR1Status", "");
		$this->RegisterPropertyString("WR1Error", "");
		$this->RegisterPropertyString("WR1Pdc1", "");
		$this->RegisterPropertyString("WR1Pdc2", "");
		$this->RegisterPropertyString("WR1Pdc3", "");
		$this->RegisterPropertyString("WR1Udc1", "");
		$this->RegisterPropertyString("WR1Udc2", "");
		$this->RegisterPropertyString("WR1Udc3", "");
		$this->RegisterPropertyString("WR1Uac", "");
		$this->RegisterPropertyString("WR1Temp", "");
		
		$this->RegisterPropertyString("WR2Pac", "");
		$this->RegisterPropertyString("WR2DaySum", "");
		$this->RegisterPropertyString("WR2Status", "");
		$this->RegisterPropertyString("WR2Error", "");
		$this->RegisterPropertyString("WR2Pdc1", "");
		$this->RegisterPropertyString("WR2Pdc2", "");
		$this->RegisterPropertyString("WR2Pdc3", "");
		$this->RegisterPropertyString("WR2Udc1", "");
		$this->RegisterPropertyString("WR2Udc2", "");
		$this->RegisterPropertyString("WR2Udc3", "");
		$this->RegisterPropertyString("WR2Uac", "");
		$this->RegisterPropertyString("WR2Temp", "");
				
		// Updates einstellen
		$this->RegisterTimer("Update", $this->ReadPropertyInteger("Update")*60*1000, 'PV_Update($_IPS[\'TARGET\']);');
	}
	// Überschreibt die intere IPS_ApplyChanges($id) Funktion
	public function ApplyChanges() {
		// Diese Zeile nicht löschen
		parent::ApplyChanges();
		if (IPS_GetKernelRunlevel ( ) == 10103) {
			$archiv = IPS_GetInstanceIDByName("Archiv", 0 );
			// Variablen anlegen und auch gleich dafür sorgen, dass sie geloggt werd
			// Zunächst die Variablen für die Gesamtanlage
			AC_SetLoggingStatus($archiv, $this->RegisterVariableFloat("PVLeistungAC", "PV Leistung AC", "Elektrizitaet.Leistung", 10), true);
			AC_SetLoggingStatus($archiv, $this->RegisterVariableFloat("PVErzeugteEnergie", "PV erzeugte Energie", "Elektrizitaet.Verbrauch", 20), true);
			AC_SetAggregationType($archiv, $this->GetIDforIdent("PVErzeugteEnergie"), 1);
			AC_SetLoggingStatus($archiv, $this->RegisterVariableFloat("PVRollierenderJahresertrag", "PV rollierender Jahresertrag", "Elektrizitaet.Verbrauch", 30), true);
						
			// Jetzt die Variablen für den Wechselrichter 1
			if ($this->ReadPropertyInteger("LeistungWR1")>0) {
				if (strlen($this->ReadPropertyString("WR1Pac"))>0) 	AC_SetLoggingStatus($archiv, $this->RegisterVariableFloat("WR1LeistungAC", "WR1 Leistung AC", "Elektrizitaet.Leistung", 100), true);
				if (strlen($this->ReadPropertyString("WR1DaySum"))>0) 	AC_SetLoggingStatus($archiv, $this->RegisterVariableFloat("WR1ErzeugteEnergie", "WR1 erzeugte Energie", "Elektrizitaet.Verbrauch", 110), true);
				if (strlen($this->ReadPropertyString("WR1DaySum"))>0)	AC_SetLoggingStatus($archiv, $this->RegisterVariableFloat("WR1RollierenderJahresertrag", "WR1 rollierender Jahresertrag", "Elektrizitaet.Verbrauch", 120), true);
				if (strlen($this->ReadPropertyString("WR1Pdc1"))>0) 	AC_SetLoggingStatus($archiv, $this->RegisterVariableFloat("WR1LeistungDC1", "WR1 Leistung DC1", "Elektrizitaet.Leistung", 130), true);
				if (strlen($this->ReadPropertyString("WR1Pdc2"))>0) 	AC_SetLoggingStatus($archiv, $this->RegisterVariableFloat("WR1LeistungDC2", "WR1 Leistung DC2", "Elektrizitaet.Leistung", 130), true);
				if (strlen($this->ReadPropertyString("WR1Pdc3"))>0) 	AC_SetLoggingStatus($archiv, $this->RegisterVariableFloat("WR1LeistungDC3", "WR1 Leistung DC3", "Elektrizitaet.Leistung", 130), true);
				if (strlen($this->ReadPropertyString("WR1Udc1"))>0) 	AC_SetLoggingStatus($archiv, $this->RegisterVariableFloat("WR1SpannungDC1", "WR1 Spannung DC1", "Elektrizitaet.Spannung_DC", 140), true);
				if (strlen($this->ReadPropertyString("WR1Udc2"))>0) 	AC_SetLoggingStatus($archiv, $this->RegisterVariableFloat("WR1SpannungDC2", "WR1 Spannung DC2", "Elektrizitaet.Spannung_DC", 140), true);
				if (strlen($this->ReadPropertyString("WR1Udc3"))>0) 	AC_SetLoggingStatus($archiv, $this->RegisterVariableFloat("WR1SpannungDC3", "WR1 Spannung DC3", "Elektrizitaet.Spannung_DC", 140), true);
				if (strlen($this->ReadPropertyString("WR1Temp"))>0) 	AC_SetLoggingStatus($archiv, $this->RegisterVariableFloat("WR1Temperatur", "WR1 Temperatur", "~Temperature", 150), true);
				if (strlen($this->ReadPropertyString("WR1Uac"))>0)  	AC_SetLoggingStatus($archiv, $this->RegisterVariableFloat("WR1Wirkungsgrad", "WR1 Wirkungsgrad", "Elektrizitaet.Wirkungsgrad", 160), true);
				if (strlen($this->ReadPropertyString("WR1Uac"))>0)  	AC_SetLoggingStatus($archiv, $this->RegisterVariableFloat("WR1SpannungAC", "WR1 Spannung AC", "Elektrizitaet.Spannung_230V", 170), true);
				if (strlen($this->ReadPropertyString("WR1Status"))>0)  	AC_SetLoggingStatus($archiv, $this->RegisterVariableInteger("WR1Status", "WR1 Status", "", 180), true);
				if (strlen($this->ReadPropertyString("WR1Error"))>0)  	AC_SetLoggingStatus($archiv, $this->RegisterVariableInteger("WR1Fehlermeldung", "WR1 Fehlermeldung", "", 190), true);
			}
			
			// Jetzt die Variablen für den Wechselrichter 2
			if ($this->ReadPropertyInteger("LeistungWR2")>0) {
				if (strlen($this->ReadPropertyString("WR2Pac"))>0) 	AC_SetLoggingStatus($archiv, $this->RegisterVariableFloat("WR2LeistungAC", "WR2 Leistung AC", "Elektrizitaet.Leistung", 200), true);
				if (strlen($this->ReadPropertyString("WR2DaySum"))>0) 	AC_SetLoggingStatus($archiv, $this->RegisterVariableFloat("WR2ErzeugteEnergie", "WR2 erzeugte Energie", "Elektrizitaet.Verbrauch", 210), true);
				if (strlen($this->ReadPropertyString("WR2DaySum"))>0)	AC_SetLoggingStatus($archiv, $this->RegisterVariableFloat("WR2RollierenderJahresertrag", "WR2 rollierender Jahresertrag", "Elektrizitaet.Verbrauch", 220), true);
				if (strlen($this->ReadPropertyString("WR2Pdc1"))>0) 	AC_SetLoggingStatus($archiv, $this->RegisterVariableFloat("WR2LeistungDC1", "WR2 Leistung DC1", "Elektrizitaet.Leistung", 230), true);
				if (strlen($this->ReadPropertyString("WR2Pdc2"))>0) 	AC_SetLoggingStatus($archiv, $this->RegisterVariableFloat("WR2LeistungDC2", "WR2 Leistung DC2", "Elektrizitaet.Leistung", 230), true);
				if (strlen($this->ReadPropertyString("WR2Pdc3"))>0) 	AC_SetLoggingStatus($archiv, $this->RegisterVariableFloat("WR2LeistungDC3", "WR2 Leistung DC3", "Elektrizitaet.Leistung", 230), true);
				if (strlen($this->ReadPropertyString("WR2Udc1"))>0) 	AC_SetLoggingStatus($archiv, $this->RegisterVariableFloat("WR2SpannungDC1", "WR2 Spannung DC1", "Elektrizitaet.Spannung_DC", 240), true);
				if (strlen($this->ReadPropertyString("WR2Udc2"))>0) 	AC_SetLoggingStatus($archiv, $this->RegisterVariableFloat("WR2SpannungDC2", "WR2 Spannung DC2", "Elektrizitaet.Spannung_DC", 240), true);
				if (strlen($this->ReadPropertyString("WR2Udc3"))>0) 	AC_SetLoggingStatus($archiv, $this->RegisterVariableFloat("WR2SpannungDC3", "WR2 Spannung DC3", "Elektrizitaet.Spannung_DC", 240), true);
				if (strlen($this->ReadPropertyString("WR2Temp"))>0) 	AC_SetLoggingStatus($archiv, $this->RegisterVariableFloat("WR2Temperatur", "WR2 Temperatur", "~Temperature", 250), true);
				if (strlen($this->ReadPropertyString("WR2Uac"))>0)  	AC_SetLoggingStatus($archiv, $this->RegisterVariableFloat("WR2Wirkungsgrad", "WR2 Wirkungsgrad", "Elektrizitaet.Wirkungsgrad", 260), true);
				if (strlen($this->ReadPropertyString("WR2Uac"))>0)  	AC_SetLoggingStatus($archiv, $this->RegisterVariableFloat("WR2SpannungAC", "WR2 Spannung AC", "Elektrizitaet.Spannung_230V", 270), true);
				if (strlen($this->ReadPropertyString("WR2Status"))>0)  	AC_SetLoggingStatus($archiv, $this->RegisterVariableInteger("WR2Status", "WR2 Status", "", 280), true);
				if (strlen($this->ReadPropertyString("WR2Error"))>0)  	AC_SetLoggingStatus($archiv, $this->RegisterVariableInteger("WR2Fehlermeldung", "WR2 Fehlermeldung", "", 290), true);
			}
		
		
		
		
		}
		
		
		//Timerzeit setzen in Minuten
		$this->SetTimerInterval("Update", $this->ReadPropertyInteger("Update")*60*1000);
	}
	
	// Berechnung der jeweiligen Jahreswerte
	private function RollierenderJahreswert(Integer $VariableID) {
		//Den Datensatz von vor 365 Tagen abfragen (zur Berücksichtigung von Schaltjahren)
		$historischeWerte = AC_GetLoggedValues($this->ReadPropertyInteger("Archiv"), $VariableID , time()-1000*24*60*60, time()-365*24*60*60, 1);
		$wertVor365d = 0;
		foreach($historischeWerte as $wertVorEinemJahr) {
			$wertVor365d = $wertVorEinemJahr['Value'];
		}
		return (GetValue($VariableID) - $wertVor365d);
	}
	
	// Umwandeln der ExcelSpalten in Zahlen
	private function getIndex(String $index) {
		if (strlen($index) == 1)
			return (ord(strtoupper($index))-64) - 1;
		if (strlen($index) == 2)
			return (ord(strtoupper(substr($index,0,1)))-64)*26 + (ord(strtoupper(substr($index,1,1)))-64) - 1;
		return 0;
	}
	
	private function monatsWerteSpeichern($monatsWerte, $year, $month, $zielVariableID, $arrayKey) {
	//Array richtig sortieren für IP-Symcon
	
		array_multisort( $monatsWerte, SORT_ASC);

		// Jetzt wird die entsprechende Historie Datei geschrieben

		$max = sizeof($monatsWerte);
		$tempMonatsWert = 0;
		if ($max > 0 ) {
			$csvFile = "C:\\IP-Symcon\\db\\".$year."\\".str_pad($month, 2 ,'0', STR_PAD_LEFT)."\\".$zielVariableID.".csv";
			$fp = fopen($csvFile, 'w');
			for($i=0; $i<$max; $i++) {
				if ($i==0 || $tempMonatsWert<>$monatsWerte[$i][$arrayKey])
					fputs($fp, $monatsWerte[$i]['time'].",".$monatsWerte[$i][$arrayKey].PHP_EOL);
					$tempMonatsWert = $monatsWerte[$i][$arrayKey];
			}
			fclose($fp);
		}
	}
	
	
	// Aktualisiert die Daten der Solaranlage
	public function Update() {
		
		set_time_limit (300);
		ini_set('memory_limit', '64M');
		
		$ftpServer   = $this->ReadPropertyString("ServerAdresse");
		$ftpUserName = $this->ReadPropertyString("Username");
		$ftpPassword = $this->ReadPropertyString("Password"); 
		$remoteDir   = $this->ReadPropertyString("RemoteDir");
		$localDir    = $this->ReadPropertyString("LocalDir");
		
		// Daten vom FTP Server holen
		// $connID = ftp_connect($ftpServer);
		$connID = false;
		if ($connID != false) {
			// Login mit Benutzername und Passwort
			$loginResult = ftp_login($connID, $ftpUserName, $ftpPassword);
			
			IPS_LogMessage("SolarlogPVSystem", "Prüfe FTP-Server Verzeichnis ".$remoteDir." auf neue Dateien.\n");
	    		ftp_chdir($connID, "./".$remoteDir);
			$remoteDirContent = ftp_nlist($connID, ".");
	    		foreach($remoteDirContent as $index2 => $remoteFile) {
				if (substr($remoteFile, -3) == "csv") {
					$localFile = $localDir.$remoteFile;
		      			$localFile = str_replace("/","\\",$localFile);
		      			$timeRemoteModified = ftp_mdtm($connID, $remoteFile );
					if (file_exists($localFile)) $timeLocalModified = filemtime($localFile);
			   		if (!file_exists($localFile) || $timeRemoteModified>$timeLocalModified) {
			      			if (ftp_get($connID, $localFile, $remoteFile, FTP_BINARY)) {
		   	 				IPS_LogMessage("SolarlogPVSystem", $localFile." wurde erfolgreich geschrieben ");
	         	   				touch($localFile, $timeRemoteModified);
						} else {
		    					IPS_LogMessage("SolarlogPVSystem", "Bei Datei $localFile ist in Fehler ist aufgetreten\n");
						}
					}
				}
		 	}
		} else {
			IPS_LogMessage("SolarlogPVSystem", "FTP Server nicht erreichbar!\n");
		}

		IPS_LogMessage("SolarlogPVSystem","Solarlog Dateien sind jetzt aktuell");
		// ftp_close($connID);
		
		// Daten einlesen
		IPS_LogMessage("SolarlogPVSystem","Die Daten der Solarlog Dateien werden jetzt eingelesen");
		
		// Buchstaben in Indexwerte umwandeln.
		$WR1PacIdx 	= $this->getIndex($this->ReadPropertyString("WR1Pac"));
		$WR1DaySumIdx 	= $this->getIndex($this->ReadPropertyString("WR1DaySum"));
		$WR1StatusIdx 	= $this->getIndex($this->ReadPropertyString("WR1Status"));
		$WR1ErrorIdx 	= $this->getIndex($this->ReadPropertyString("WR1Error"));
		$WR1Pdc1Idx 	= $this->getIndex($this->ReadPropertyString("WR1Pdc1"));
		$WR1Pdc2Idx 	= $this->getIndex($this->ReadPropertyString("WR1Pdc2"));
		$WR1Pdc3Idx 	= $this->getIndex($this->ReadPropertyString("WR1Pdc3"));
		$WR1Udc1Idx 	= $this->getIndex($this->ReadPropertyString("WR1Udc1"));
		$WR1Udc2Idx 	= $this->getIndex($this->ReadPropertyString("WR1Udc2"));
		$WR1Udc3Idx 	= $this->getIndex($this->ReadPropertyString("WR1Udc3"));
		$WR1UacIdx 	= $this->getIndex($this->ReadPropertyString("WR1Uac"));
		
		$WR2PacIdx 	= $this->getIndex($this->ReadPropertyString("WR2Pac"));
		$WR2DaySumIdx 	= $this->getIndex($this->ReadPropertyString("WR2DaySum"));
		$WR2StatusIdx 	= $this->getIndex($this->ReadPropertyString("WR2Status"));
		$WR2ErrorIdx 	= $this->getIndex($this->ReadPropertyString("WR2Error"));
		$WR2Pdc1Idx 	= $this->getIndex($this->ReadPropertyString("WR2Pdc1"));
		$WR2Pdc2Idx 	= $this->getIndex($this->ReadPropertyString("WR2Pdc2"));
		$WR2Pdc3Idx 	= $this->getIndex($this->ReadPropertyString("WR2Pdc3"));
		$WR2Udc1Idx 	= $this->getIndex($this->ReadPropertyString("WR2Udc1"));
		$WR2Udc2Idx 	= $this->getIndex($this->ReadPropertyString("WR2Udc2"));
		$WR2Udc3Idx 	= $this->getIndex($this->ReadPropertyString("WR2Udc3"));
		$WR2UacIdx 	= $this->getIndex($this->ReadPropertyString("WR2Uac"));
		
		$zwischenWerte = array();
		$zwischenWerte['WR1DaySum'] = 0;
		$zwischenWerte['WR2DaySum'] = 0;
		
		for ($year=2000; $year<= date("Y"); $year++) {
			for ($month=1; $month<=12; $month++) {
				if ($year == date("Y") && $month > date("n")) break;
				IPS_LogMessage("SolarlogPVSystem", "Lese die Daten aus ".$month."-".$year." ein");
		
				$monatsWerte = array();
				$row = 0;
				for ($day=1; $day<=cal_days_in_month(CAL_GREGORIAN, $month, $year); $day++) {
					$csvFile = $localDir."9001\min".substr($year,2,2).str_pad($month, 2 ,'0', STR_PAD_LEFT).str_pad($day, 2 ,'0', STR_PAD_LEFT).".csv";

					if (file_exists($csvFile)) {
						if (($handle = fopen($csvFile, "r")) !== FALSE) {
							$firstRowThisDay = $row;
							while (($csvdata = fgetcsv($handle, 0, ";")) !== FALSE) {
									$num = count($csvdata);
								if ($csvdata[0] != "#Datum" && $csvdata[0] != "#Date") {
									if (strlen($csvdata[0]) == 8) $date_time = DateTime::createFromFormat('d.m.y H:i:s', $csvdata[0]." ".$csvdata[1]);
									if (strlen($csvdata[0]) == 10) $date_time = DateTime::createFromFormat('d.m.Y H:i:s', $csvdata[0]." ".$csvdata[1]);
										$monatsWerte[$row]['time']   = $date_time->getTimestamp();

									// Daten aus der CSV in das monatsWerte Array überführen
									$monatsWerte[$row]['WR1Pac'] 	= $csvdata[$WR1PacIdx];
									$monatsWerte[$row]['WR1DaySum'] = $csvdata[$WR1DaySumIdx] / 1000 + $zwischenWerte['WR1DaySum'];;
									if ($WR1StatusIdx<>0) $monatsWerte[$row]['WR1Status'] 	= $csvdata[$WR1StatusIdx];
									if ($WR1ErrorIdx<>0) $monatsWerte[$row]['WR1Error'] 	= $csvdata[$WR1ErrorIdx];
									if ($WR1Pdc1Idx<>0) $monatsWerte[$row]['WR1Pdc1'] 	= $csvdata[$WR1Pdc1Idx];
									if ($WR1Pdc2Idx<>0) $monatsWerte[$row]['WR1Pdc2'] 	= $csvdata[$WR1Pdc2Idx];
									if ($WR1Pdc3Idx<>0) $monatsWerte[$row]['WR1Pdc3'] 	= $csvdata[$WR1Pdc3Idx];
									if ($WR1Udc1Idx<>0) $monatsWerte[$row]['WR1Udc1'] 	= $csvdata[$WR1Udc1Idx];
									if ($WR1Udc2Idx<>0) $monatsWerte[$row]['WR1Udc2'] 	= $csvdata[$WR1Udc2Idx];
									if ($WR1Udc3Idx<>0) $monatsWerte[$row]['WR1Udc3'] 	= $csvdata[$WR1Udc3Idx];
									if ($WR1UacIdx<>0) $monatsWerte[$row]['WR1Uac'] 	= $csvdata[$WR1UacIdx];

									if ($monatsWerte[$row]['WR1Pdc1'] > 0 )
										$monatsWerte[$row]['WR1Eff']= $monatsWerte[$row]['WR1Pac']*100 / $monatsWerte[$row]['WR1Pdc1'];
									else
										$monatsWerte[$row]['WR1Eff']=0;
									
									$monatsWerte[$row]['WR2Pac'] 	= $csvdata[$WR2PacIdx];
									$monatsWerte[$row]['WR2DaySum'] = $csvdata[$WR2DaySumIdx] / 1000 + $zwischenWerte['WR2DaySum'];;
									if ($WR2StatusIdx<>0) $monatsWerte[$row]['WR2Status'] 	= $csvdata[$WR2StatusIdx];
									if ($WR2ErrorIdx<>0) $monatsWerte[$row]['WR2Error'] 	= $csvdata[$WR2ErrorIdx];
									if ($WR2Pdc1Idx<>0) $monatsWerte[$row]['WR2Pdc1'] 	= $csvdata[$WR2Pdc1Idx];
									if ($WR2Pdc2Idx<>0) $monatsWerte[$row]['WR2Pdc2'] 	= $csvdata[$WR2Pdc2Idx];
									if ($WR2Pdc3Idx<>0) $monatsWerte[$row]['WR2Pdc3'] 	= $csvdata[$WR2Pdc3Idx];
									if ($WR2Udc1Idx<>0) $monatsWerte[$row]['WR2Udc1'] 	= $csvdata[$WR2Udc1Idx];
									if ($WR2Udc2Idx<>0) $monatsWerte[$row]['WR2Udc2'] 	= $csvdata[$WR2Udc2Idx];
									if ($WR2Udc3Idx<>0) $monatsWerte[$row]['WR2Udc3'] 	= $csvdata[$WR2Udc3Idx];
									if ($WR2UacIdx<>0) $monatsWerte[$row]['WR2Uac'] 	= $csvdata[$WR2UacIdx];
									
									if ($WR2Pdc1Idx<>0)
										if ($monatsWerte[$row]['WR2Pdc1'] > 0 )
											$monatsWerte[$row]['WR2Eff']= $monatsWerte[$row]['WR2Pac']*100 / $monatsWerte[$row]['WR2Pdc1'];
										else
											$monatsWerte[$row]['WR2Eff']=0;
									$row++;
								}  // if
					 		}  // while
							$zwischenWerte['WR1DaySum'] = $monatsWerte[2]['WR1DaySum'];
							$zwischenWerte['WR2DaySum'] = $monatsWerte[2]['WR2DaySum'];
	   		 				fclose($handle);
	  					}  // if handle
		 			}  // if file exists
				}  // for-schleife days
							   
				$this->monatsWerteSpeichern($monatsWerte, $year, $month, $this->GetIDforIdent("WR1LeistungAC"), "WR1Pac");
				$this->monatsWerteSpeichern($monatsWerte, $year, $month, $this->GetIDforIdent("WR1LeistungDC1"), "WR1Pdc1");
				$this->monatsWerteSpeichern($monatsWerte, $year, $month, $this->GetIDforIdent("WR1Wirkungsgrad"), "WR1Eff");
				$this->monatsWerteSpeichern($monatsWerte, $year, $month, $this->GetIDforIdent("WR1ErzeugteEnergie"), "WR1DaySum");
			
			} //for-schleife month
		} //for-schleife-years
		
		if (AC_ReAggregateVariable( $archiv, $this->GetIDforIdent("WR1LeistungAC")));
		sleep(5);
		if (AC_ReAggregateVariable( $archiv, $this->GetIDforIdent("WR1LeistungDC1")));
		sleep(5);
		if (AC_ReAggregateVariable( $archiv, $this->GetIDforIdent("WR1Wirkungsgrad")));
		sleep(5);
		if (AC_ReAggregateVariable( $archiv, $this->GetIDforIdent("WR1ErzeugteEnergie")));
		
	}
 }
