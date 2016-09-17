<?
    // Klassendefinition
class BEST_Bottrop_Muelltage extends IPSModule{
    
   // Der Konstruktor des Moduls
   // Überschreibt den Standard Kontruktor von IPS
   public function __construct($InstanceID){
         // Diese Zeile nicht löschen
         parent::__construct($InstanceID);
         // Selbsterstellter Code
  	}

   public function Create(){
     	 // Diese Zeile nicht löschen.
        parent::Create();

		$this->RegisterPropertyString("Strasse", "Ernst-Wilczok-Platz");
		$this->RegisterPropertyString("Nummer", "1");
        $this->RegisterPropertyInteger("Wochentag", 64);
		$this->RegisterPropertyString("UpdateInterval", "00:00" );
        $this->RegisterPropertyBoolean("PushMsgAktiv", false);
        $this->RegisterPropertyInteger("WebFrontInstanceID", "");
        $this->RegisterPropertyString("UpdatePushNachricht", "00:00" );
    }

        // Überschreibt die intere IPS_ApplyChanges($id) Funktion
   public function ApplyChanges(){
         // Diese Zeile nicht löschen
         parent::ApplyChanges();

			if (($this->ReadPropertyString("Strasse") != "") AND ($this->ReadPropertyString("Nummer") != ""))
				{
                            //Variablen erstellen
                    $this->RegisterVariableString("Woche_String","Wochenübersicht","HTMLBox",5);

		                    //Timer zeit setzen
                    if ($this->ReadPropertyString("UpdateInterval") != "")
                    {
                        $Updatetime = explode(":",$this->ReadPropertyString("UpdateInterval"));
                        $this->SetTimerkwByName($this->InstanceID,"Abholtage_Update",$this->ReadPropertyInteger("Wochentag"),$Updatetime[0],$Updatetime[1]);
                    }
                    if (($this->ReadPropertyString("UpdatePushNachricht") != "") AND ($this->ReadPropertyBoolean("PushMsgAktiv") == true))
                    {
                        $Updatetime = explode(":",$this->ReadPropertyString("UpdatePushNachricht"));
                        $this->SetTimerEveryday($this->InstanceID,"Push_Nachricht",$Updatetime[0],$Updatetime[1]);
                    }

                            // Push Nachrichten aktivieren / deaktivieren
                        IPS_SetEventActive(@IPS_GetEventIDByName("Push_Nachricht",$this->InstanceID), $this->ReadPropertyBoolean("PushMsgAktiv"));
                             //Instanz ist aktiv
			        $this->SetStatus(102);
				}
			else
				{
				//Instanz ist inaktiv
				$this->SetStatus(104);
				}
   	}

   public function Update()
	{

            $Strasse =  $this->ReadPropertyString("Strasse");  // Straßenname
            $Nummer = $this->ReadPropertyString("Nummer");  // Hausnummer
            // Seite von der BEST auslesen
			$html = file_get_contents("http://www.best-bottrop.de/abfallkalender/start/uebersicht?strassenname=".$Strasse."&hausnummer=".$Nummer);
			// Prüfe ob  "BEST Bottrop Abfuhrkalender" im Quellcode vorhanden
			if(strpos($html, "BEST Bottrop Abfuhrkalender") === false){
				echo "Ungültige Adresse!";
				return;
			}

			while(true){
				//Zwischen <strong> und </strong> suchen nach Tonnen Typ (Grau, Braun, Gelb, Blau)
				$html = stristr($html,  '<strong>Termine');
				if($html === false)
						break;
				$html = stristr($html, '>');
				$TonnenTyp = substr($html, 22, strpos($html, "</strong>")-22);  // Tonnen Typ eintragen
				//Zwischen <tr><td class="b"> und </td></tr> suchen nach Abholdatum
				for ($i=0; $i < 3; $i++) { 
					$html = stristr($html, '<tr><td class="b">');
					if($html === false)
						break;
					$html = stristr($html, '>');
					$Termin = substr($html, 15, strpos($html, "</td></tr>")-15);  // Abholdatum eintragen
					$Abholtage[$TonnenTyp][$i] = $Termin;  //Array Abholtage
				}
 			}
            // Termindaten Array in Json umwandeln und in Buffer speichern 
            $this->SetBuffer("Termine",json_encode($Abholtage));

            // Datum aus dem Array Abholtage in Unix Timestamp umwandeln 
            $TerminBlau=strtotime($Abholtage['blaue Tonne'][0]);  
            $TerminGrau=strtotime($Abholtage['graue Tonne'][0]);
            $TerminGelb=strtotime($Abholtage['gelbe Tonne'][0]);
            $TerminBraun=strtotime($Abholtage['braune Tonne'][0]);
            $kw = date("W",time()); //Kalneder Woche 
            // HTML Box leeren
            SetValue($this->GetIDForIdent("Woche_String"),"");
            
            /// HTML BOX Inhalt Tabbele erstellen
            $Wochestr="<link rel='stylesheet' href='https://maxcdn.bootstrapcdn.com/font-awesome/4.5.0/css/font-awesome.min.css'>";
            $Wochestr.= "<table width='100%' cellspacing='2' cellpadding='2'>";
            if (date("W",$TerminBraun) == $kw+1)
	            $Wochestr.= "<td width='25%' style='color:#A95A57'><i class='fa fa-trash fa-2x'></i> ".$this->Wochentag($TerminBraun)."</td>";
            if (date("W",$TerminBlau) == $kw+1)
	            $Wochestr.= "<td width='25%' style='color:#1B6DB7'><i class='fa fa-trash fa-2x'></i> ".$this->Wochentag($TerminBlau)."</td>";
            if (date("W",$TerminGelb) == $kw+1)
                 $Wochestr.= "<td width='25%' style='color:#F9E21B'><i class='fa fa-trash fa-2x'></i> ".$this->Wochentag($TerminGelb)."</td>";
            if (date("W",$TerminGrau) == $kw+1)
	            $Wochestr.= "<td width='25%' style='color:#9E9E9E'><i class='fa fa-trash fa-2x'></i> ".$this->Wochentag($TerminGrau)."</td>";
            $Wochestr.= "</table>";

            //HTML Box Inhalt schreiben
            SetValue($this->GetIDForIdent("Woche_String"),$Wochestr);

            return IPS_LogMessage("Best Abfall Kalender", "Daten wurden aktualisiert");
    }
    
   public function PushNachricht(){

        $Bufferdata = $this->GetBuffer("Termine");
        $Abholtage= json_decode($Bufferdata,TRUE);  

	    foreach ($Abholtage as $Tonnentyp => $Datum){
		    if (mktime(0, 0, 0, date("m") , date("d")+1, date("Y")) == strtotime($Datum[0])){
                $WebFrontIns = $this->ReadPropertyInteger("WebFrontInstanceID");
                if ($WebFrontIns != "")
		            WFC_PushNotification($WebFrontIns, 'Mülltonnen', 'Morgen wird die '.$Tonnentyp.' abgeholt!', '', 0);
		    }
		}   
    }


   public function Termine(string $Tonne,int $Datensatz)
   {
        $Bufferdata = $this->GetBuffer("Termine");
        $Termindaten = json_decode($Bufferdata,TRUE);
        $TonnenTyp = array('graue Tonne','blaue Tonne', 'gelbe Tonne', 'braune Tonne');

        if (empty($Tonne) AND empty($Datensatz) ) {   
                return $Termindaten; 
        }
        elseif (in_array($Tonne, $TonnenTyp)) {
            return strtotime($Termindaten[$Tonne][$Datensatz]);
        }
        else {
            echo "Tonnentyp '".$Tonne."' nicht gefunden !";
            IPS_LogMessage("Best Abfall Kalender", "FEHLER - Tonnentyp '".$Tonne."' nicht gefunden !");
       		exit;
        }           
   }
    
   private function SetTimerkwByName($parentID, $name,$day,$hour,$minutes)
    {
        $eid = @IPS_GetEventIDByName($name, $parentID);
        if($eid === false)
	    {
            $eid = IPS_CreateEvent(1);
            IPS_SetParent($eid, $parentID);
            IPS_SetName($eid, $name);
        }
        else
	    {
		    IPS_SetEventCyclic($eid, 3 /*  	Wöchentlich */ , 1 /* Alle X Wochen*/ ,$day,/*Sonntag*/ 0,0,0);
		    IPS_SetEventCyclicTimeFrom($eid, $hour, $minutes, 0);
            IPS_SetEventScript($eid, 'BT_Update($_IPS["TARGET"]);');
		    IPS_SetEventActive($eid, true);
            IPS_SetHidden($eid, true);
            return $eid;
	    }
    }
    
   private function SetTimerEveryday($parentID, $name, $hour,$minutes)
    {
    $eid = @IPS_GetEventIDByName($name, $parentID);
        if($eid === false)
	    {
            $eid = IPS_CreateEvent(1);
            IPS_SetParent($eid, $parentID);
            IPS_SetName($eid, $name);
        }
        else
        {
            IPS_SetEventCyclic($eid, 2 /*  	Täglich */ , 0 ,0,0,0,0);
            IPS_SetEventCyclicTimeFrom($eid, $hour, $minutes, 0);
            IPS_SetEventScript($eid, 'BT_PushNachricht($_IPS["TARGET"]);');
		    IPS_SetEventActive($eid, true);
            IPS_SetHidden($eid, true);
        }

    }
    
   private Function Wochentag($Tag){
        $Wochentage     = array("So","Mo","Di","Mi","Do","Fr","Sa");
        $Wochentag      = date("N",$Tag);
        return $Wochentage[$Wochentag]." / ".date("d.m.Y",$Tag) ; 
    }

}
?>