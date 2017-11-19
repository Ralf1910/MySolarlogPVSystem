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
			AC_SetLoggingStatus($archiv, $this->RegisterVariableInteger("PVLeistung", "PV Leistung AC", "", 10), true);
			AC_SetLoggingStatus($archiv, $this->RegisterVariableFloat("PVErzeugteEnergie", "PV erzeugte Energie", "", 20), true);
			AC_SetAggregationType($archiv, $this->GetIDforIdent("PVErzeugteEnergie"), 1);
			AC_SetLoggingStatus($archiv, $this->RegisterVariableInteger("WR1LeistungAC", "WR1 Leistung AC", "", 30), true);
			AC_SetLoggingStatus($archiv, $this->RegisterVariableFloat("WR1ErzeugteEnergie", "WR1 erzeugte Energie", "", 40), true);
			AC_SetLoggingStatus($archiv, $this->RegisterVariableInteger("WR1LeistungDC1", "WR1 Leistung DC1", "", 50), true);
			AC_SetLoggingStatus($archiv, $this->RegisterVariableInteger("WR1LeistungDC2", "WR1 Leistung DC2", "", 60), true);
			AC_SetLoggingStatus($archiv, $this->RegisterVariableInteger("WR1LeistungDC3", "WR1 Leistung DC3", "", 70), true);
			AC_SetLoggingStatus($archiv, $this->RegisterVariableInteger("WR1SpannungDC1", "WR1 Spannung DC1", "", 80), true);
			AC_SetLoggingStatus($archiv, $this->RegisterVariableInteger("WR1SpannungDC2", "WR1 Spannung DC2", "", 90), true);
			AC_SetLoggingStatus($archiv, $this->RegisterVariableInteger("WR1SpannungDC3", "WR1 Spannung DC3", "", 100), true);
			AC_SetLoggingStatus($archiv, $this->RegisterVariableInteger("WR1Temperatur", "WR1 Temperatur", "", 110), true);
			AC_SetLoggingStatus($archiv, $this->RegisterVariableFloat("WR1Wirkungsgrad", "WR1 Wirkungsgrad", "", 120), true);
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
		$WR1PacIdx = $this->getIndex($this->ReadPropertyString("WR1Pac"));
		$WRDaySumIdx = $this->getIndex($this->ReadPropertyString("WR1DaySum"));
		
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
									$monatsWerte[$row]['WR1Status'] = $csvdata[$this->ReadPropertyString("WR1Status")];
									$monatsWerte[$row]['WR1Error'] 	= $csvdata[$this->ReadPropertyString("WR1Error")];
									$monatsWerte[$row]['WR1Pdc1'] 	= $csvdata[$this->ReadPropertyString("WR1Pdc1")];
									$monatsWerte[$row]['WR1Pdc2'] 	= $csvdata[$this->ReadPropertyString("WR1Pdc2")];
									$monatsWerte[$row]['WR1Pdc3'] 	= $csvdata[$this->ReadPropertyString("WR1Pdc3")];
									$monatsWerte[$row]['WR1Udc1'] 	= $csvdata[$this->ReadPropertyString("WR1Udc1")];
									$monatsWerte[$row]['WR1Udc2'] 	= $csvdata[$this->ReadPropertyString("WR1Udc2")];
									$monatsWerte[$row]['WR1Udc3'] 	= $csvdata[$this->ReadPropertyString("WR1Udc3")];
									$monatsWerte[$row]['WR1Uac'] 	= $csvdata[$this->ReadPropertyString("WR1Uac")];

									if ($monatsWerte[$row]['WR1Pdc1'] > 0 )
										$monatsWerte[$row]['WR1Eff']= $monatsWerte[$row]['WR1Pac']*100 / $monatsWerte[$row]['WR1Pdc1'];
									else
										$monatsWerte[$row]['WR1Eff']=0;
									
									$monatsWerte[$row]['WR2Pac'] 	= $csvdata[$this->ReadPropertyString("WR2Pac")];
									$monatsWerte[$row]['WR2DaySum'] = $csvdata[$this->ReadPropertyString("WR2DaySum")];
									$monatsWerte[$row]['WR2Status'] = $csvdata[$this->ReadPropertyString("WR2Status")];
									$monatsWerte[$row]['WR2Error'] 	= $csvdata[$this->ReadPropertyString("WR2Error")];
									$monatsWerte[$row]['WR2Pdc1'] 	= $csvdata[$this->ReadPropertyString("WR2Pdc1")];
									$monatsWerte[$row]['WR2Pdc2'] 	= $csvdata[$this->ReadPropertyString("WR2Pdc2")];
									$monatsWerte[$row]['WR2Pdc3'] 	= $csvdata[$this->ReadPropertyString("WR2Pdc3")];
									$monatsWerte[$row]['WR2Udc1'] 	= $csvdata[$this->ReadPropertyString("WR2Udc1")];
									$monatsWerte[$row]['WR2Udc2'] 	= $csvdata[$this->ReadPropertyString("WR2Udc2")];
									$monatsWerte[$row]['WR2Udc3'] 	= $csvdata[$this->ReadPropertyString("WR2Udc3")];
									$monatsWerte[$row]['WR2Uac'] 	= $csvdata[$this->ReadPropertyString("WR2Uac")];
									$row++;
								}  // if
					 		}  // while
							$zwischenWerte['WR1DaySum'] = $monatsWerte[$firstRowThisDay]['WR1DaySum'];
	   		 				fclose($handle);
	  					}  // if handle
		 			}  // if file exists
				}  // for-schleife days
							   
				$this->monatsWerteSpeichern($monatsWerte, $year, $month, $this->GetObjectIDByName("WR1 Leistung AC"), "WR1Pac");
				$this->monatsWerteSpeichern($monatsWerte, $year, $month, $this->GetObjectIDByName("WR1 Leistung DC"), "WR1Pdc1");
				$this->monatsWerteSpeichern($monatsWerte, $year, $month, $this->GetObjectIDByName("WR1 Wirkungsgrad"), "WR1Eff");
				$this->monatsWerteSpeichern($monatsWerte, $year, $month, $this->GetObjectIDByName("WR1 erzeugte Energie"), "WR1DaySum");
			
			} //for-schleife month
		} //for-schleife-years
		/* Gesamtverbrauch zusammenaddieren
		$aktuellerVerbrauchP 	= 	0;
		if ($this->ReadPropertyInteger("VerbraucherP1")>0) $aktuellerVerbrauchP += getValue($this->ReadPropertyInteger("VerbraucherP1"));
		if ($this->ReadPropertyInteger("VerbraucherP2")>0) $aktuellerVerbrauchP += getValue($this->ReadPropertyInteger("VerbraucherP2"));
		if ($this->ReadPropertyInteger("VerbraucherP3")>0) $aktuellerVerbrauchP += getValue($this->ReadPropertyInteger("VerbraucherP3"));
		$aktuellerVerbrauchW 	= 	0;
		if ($this->ReadPropertyInteger("VerbraucherW1")>0) $aktuellerVerbrauchW += getValue($this->ReadPropertyInteger("VerbraucherW1"));
		if ($this->ReadPropertyInteger("VerbraucherW2")>0) $aktuellerVerbrauchW += getValue($this->ReadPropertyInteger("VerbraucherW2"));
		if ($this->ReadPropertyInteger("VerbraucherW3")>0) $aktuellerVerbrauchW += getValue($this->ReadPropertyInteger("VerbraucherW3"));
		// Gesamterzeugung zusammenaddieren
		$aktuelleErzeugungP		=	0;
		if ($this->ReadPropertyInteger("ErzeugerP1")>0) $aktuelleErzeugungP += getValue($this->ReadPropertyInteger("ErzeugerP1"));
		if ($this->ReadPropertyInteger("ErzeugerP2")>0) $aktuelleErzeugungP += getValue($this->ReadPropertyInteger("ErzeugerP2"));
		if ($this->ReadPropertyInteger("ErzeugerP3")>0) $aktuelleErzeugungP += getValue($this->ReadPropertyInteger("ErzeugerP3"));
		$aktuelleErzeugungW		=	0;
		if ($this->ReadPropertyInteger("ErzeugerW1")>0) $aktuelleErzeugungW += getValue($this->ReadPropertyInteger("ErzeugerW1"));
		if ($this->ReadPropertyInteger("ErzeugerW2")>0) $aktuelleErzeugungW += getValue($this->ReadPropertyInteger("ErzeugerW2"));
		if ($this->ReadPropertyInteger("ErzeugerW3")>0) $aktuelleErzeugungW += getValue($this->ReadPropertyInteger("ErzeugerW3"));
		$bezogeneEnergie			= 	getValue($this->GetIDforIdent("bezogeneEnergie"));
		$eingespeisteEnergie		=	getValue($this->GetIDforIdent("eingespeisteEnergie"));
		$gespeicherteEnergie		=	getValue($this->GetIDforIdent("gespeicherteEnergie"));
		$direktverbrauchteEnergie	= 	getValue($this->GetIDforIdent("selbstverbrauchteEnergie"));
		$maxLadeleistung			= 	$this->ReadPropertyInteger("MaxLadeleistung");
		$kapazitaet					=	$this->ReadPropertyInteger("Kapazitaet")/1000;
		$fuellstand					=	getValue($this->GetIDforIdent("fuellstand"));
		// Berechnung der Leistungswerte
		if ($aktuellerVerbrauchP > $aktuelleErzeugungP) {
			if ($fuellstand <= 0) {
				setValue($this->GetIDforIdent("aktuellerNetzbezug"), max($aktuellerVerbrauchP - $aktuelleErzeugungP,0));
				setValue($this->GetIDforIdent("aktuelleLadeleistung"), 0);
				setValue($this->GetIDforIdent("aktuelleEinspeisung"), 0);
			} else {
				setValue($this->GetIDforIdent("aktuellerNetzbezug"), max($aktuellerVerbrauchP - $aktuelleErzeugungP - $maxLadeleistung,0));
				setValue($this->GetIDforIdent("aktuelleLadeleistung"), max($aktuelleErzeugungP - $aktuellerVerbrauchP, -1*$maxLadeleistung));
				setValue($this->GetIDforIdent("aktuelleEinspeisung"), 0);
			}
		} else {
			if ($fuellstand >= $kapazitaet) {
				setValue($this->GetIDforIdent("aktuellerNetzbezug"), 0);
				setValue($this->GetIDforIdent("aktuelleLadeleistung"), 0);
				setValue($this->GetIDforIdent("aktuelleEinspeisung"), max($aktuelleErzeugungP - $aktuellerVerbrauchP,0));
			} else {
				setValue($this->GetIDforIdent("aktuellerNetzbezug"), 0);
				setValue($this->GetIDforIdent("aktuelleLadeleistung"), min($aktuelleErzeugungP - $aktuellerVerbrauchP, $maxLadeleistung));
				setValue($this->GetIDforIdent("aktuelleEinspeisung"), max($aktuelleErzeugungP - $aktuellerVerbrauchP - $maxLadeleistung,0));
			}
		}
		// Berechnung, der Energiewerte
		if ($aktuellerVerbrauchW > $aktuelleErzeugungW) {
			if ($fuellstand <= 0) {
				setValue($this->GetIDforIdent("bezogeneEnergie"), $bezogeneEnergie + max($aktuellerVerbrauchW - $aktuelleErzeugungW,0));
				setValue($this->GetIDforIdent("fuellstand"), 0);
			} else {
				setValue($this->GetIDforIdent("bezogeneEnergie"), $bezogeneEnergie + max($aktuellerVerbrauchW - $aktuelleErzeugungW - $maxLadeleistung/60000,0));
				setValue($this->GetIDforIdent("fuellstand"), max($fuellstand + max($aktuelleErzeugungW - $aktuellerVerbrauchW, -1*$maxLadeleistung/60000), 0));
			}
		} else {
			if ($fuellstand >= $kapazitaet) {
				setValue($this->GetIDforIdent("eingespeisteEnergie"), $eingespeisteEnergie + max($aktuelleErzeugungW - $aktuellerVerbrauchW,0));
				setValue($this->GetIDforIdent("fuellstand"), $kapazitaet);
			} else {
				setValue($this->GetIDforIdent("eingespeisteEnergie"), $eingespeisteEnergie + max($aktuelleErzeugungW - $aktuellerVerbrauchW - $maxLadeleistung/60000,0));
				setValue($this->GetIDforIdent("fuellstand"), min($fuellstand + min($aktuelleErzeugungW - $aktuellerVerbrauchW, $maxLadeleistung/60000), $kapazitaet));
				setValue($this->GetIDforIdent("gespeicherteEnergie"), $gespeicherteEnergie + min($aktuelleErzeugungW - $aktuellerVerbrauchW, $maxLadeleistung/60000));
			}
		}
		SetValue($this->GetIDforIdent("fuellstandProzent"), round((getValue($this->GetIDforIdent("fuellstand"))*100 / $kapazitaet)/5)*5);
		SetValue($this->GetIDforIdent("aktuelleEigennutzung"), min($aktuellerVerbrauchP, $aktuelleErzeugungP));
		SetValue($this->GetIDforIdent("selbstverbrauchteEnergie"), $direktverbrauchteEnergie + min($aktuellerVerbrauchW, $aktuelleErzeugungW));
		if (Date("i", time()) == 00) {
			SetValue($this->GetIDforIdent("zyklen"), getValue($this->GetIDforIdent("gespeicherteEnergie")) / $kapazitaet);
			if (($bezogeneEnergie + $direktverbrauchteEnergie + $gespeicherteEnergie)>0)
				SetValue($this->GetIDforIdent("EVGV"), ($direktverbrauchteEnergie + $gespeicherteEnergie)*100 / ($bezogeneEnergie + $direktverbrauchteEnergie + $gespeicherteEnergie));
			if (($eingespeisteEnergie + $direktverbrauchteEnergie + $gespeicherteEnergie)>0)
				SetValue($this->GetIDforIdent("EVGP"), ($direktverbrauchteEnergie + $gespeicherteEnergie)*100 / ($eingespeisteEnergie + $direktverbrauchteEnergie + $gespeicherteEnergie));
			SetValue($this->GetIDforIdent("rollierendeEingespeisteEnergie"), $this->RollierenderJahreswert($this->GetIDforIdent("eingespeisteEnergie")));
			SetValue($this->GetIDforIdent("rollierendeSelbstverbrauchteEnergie"), $this->RollierenderJahreswert($this->GetIDforIdent("selbstverbrauchteEnergie")));
			SetValue($this->GetIDforIdent("rollierendeBezogeneEnergie"), $this->RollierenderJahreswert($this->GetIDforIdent("bezogeneEnergie")));
			SetValue($this->GetIDforIdent("rollierendeGespeicherteEnergie"), $this->RollierenderJahreswert($this->GetIDforIdent("gespeicherteEnergie")));
			SetValue($this->GetIDforIdent("rollierendeEVGV"), $this->RollierenderJahreswert($this->GetIDforIdent("EVGV")));
			SetValue($this->GetIDforIdent("rollierendeEVGP"), $this->RollierenderJahreswert($this->GetIDforIdent("EVGP")));
			SetValue($this->GetIDforIdent("rollierendeZyklen"), $this->RollierenderJahreswert($this->GetIDforIdent("zyklen")));
		} */
	}
 }
