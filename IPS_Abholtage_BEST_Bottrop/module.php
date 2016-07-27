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
        $htis->RegisterPropertyInteger("Wochentag","1");
		$this->RegisterPropertyString("UpdateInterval", "00:00" );
        $this->RegisterPropertyBoolean("PushMsgAktiv", false);
        $this->RegisterPropertyInteger("WebFrontInstanceID", "");
        $this->RegisterPropertyString("UpdateTonneMorgen", "00:00" );
    }

        // Überschreibt die intere IPS_ApplyChanges($id) Funktion
   public function ApplyChanges(){
         // Diese Zeile nicht löschen
         parent::ApplyChanges();

			if (($this->ReadPropertyString("Strasse") != "") AND ($this->ReadPropertyString("Nummer") != ""))
				{
                            //Variablen erstellen Wetter jetzt
                    $this->RegisterVariableInteger("Graue_Tonne","Graue Tonne","UnixTimestamp",1);
                    $this->RegisterVariableInteger("Braune_Tonne","Braune Tonne","UnixTimestamp",2);
                    $this->RegisterVariableInteger("Blaue_Tonne","Blaue Tonne","UnixTimestamp",3);
                    $this->RegisterVariableInteger("Gelbe_Tonne","Gelbe Tonne","UnixTimestamp",4);
                    $this->RegisterVariableString("Woche_String","Wochenübersicht","HTMLBox",5);
		                    //Timer zeit setzen
                    if ($this->ReadPropertyString("UpdateInterval") != "")
                    {
                        $Updatetime = explode(":",$this->ReadPropertyString("UpdateInterval"));
                        $this->SetTimerWeekByName($this->InstanceID,"Abholtage_Update",$Updatetime[0],$Updatetime[1]);
                    }
                    if (($this->ReadPropertyString("UpdateTonneMorgen") != "") AND ($this->ReadPropertyBoolean("PushMsgAktiv") == true))
                    {
                        $Updatetime = explode(":",$this->ReadPropertyString("UpdateTonneMorgen"));
                        $this->SetTimerEveryday($this->InstanceID,"Push_Tonne_Morgen",$Updatetime[0],$Updatetime[1]);
                    }
                    if ($this->ReadPropertyBoolean("PushMsgAktiv") == false)
                        IPS_SetEventActive(@IPS_GetEventIDByName("Push_Tonne_Morgen",$this->InstanceID), false);
                     
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
            
            $this->SetBuffer("Termine", $Abholtage);

            // Datum aus dem Array Abholtage in Unix Timestamp umwandeln 
            $TerminBlau=strtotime($Abholtage['blaue Tonne'][0]);  
            $TerminGrau=strtotime($Abholtage['graue Tonne'][0]);
            $TerminGelb=strtotime($Abholtage['gelbe Tonne'][0]);
            $TerminBraun=strtotime($Abholtage['braune Tonne'][0]);
            
            SetValue($this->GetIDForIdent("Woche_String"),"");
            
            /// HTML BOX Inhalt Tabbele erstellen
            $week = mktime(0,0,0, date('m'), date('d')+7, date('y')); // Datum von der Folgenden Woche
            $Wochestr="<link rel='stylesheet' href='https://maxcdn.bootstrapcdn.com/font-awesome/4.5.0/css/font-awesome.min.css'>";
            $Wochestr.= "<table width='100%' cellspacing='2' cellpadding='2'>";
            if ($TerminBraun <= $week)
	            $Wochestr.= "<td width='25%' style='color:#A95A57'><i class='fa fa-trash fa-2x'></i> ".$this->Wochentag($TerminBraun)."</td>";
            if ($TerminBlau <= $week)
	            $Wochestr.= "<td width='25%' style='color:#1B6DB7'><i class='fa fa-trash fa-2x'></i> ".$this->Wochentag($TerminBlau)."</td>";
            if ($TerminGelb <= $week)
                 $Wochestr.= "<td width='25%' style='color:#F9E21B'><i class='fa fa-trash fa-2x'></i> ".$this->Wochentag($TerminGelb)."</td>";
            if ($TerminGrau <= $week)
	            $Wochestr.= "<td width='25%' style='color:#9E9E9E'><i class='fa fa-trash fa-2x'></i> ".$this->Wochentag($TerminGrau)."</td>";
            $Wochestr.= "</table>";

            
			SetValue($this->GetIDForIdent("Graue_Tonne"),$TerminGrau);
			SetValue($this->GetIDForIdent("Braune_Tonne"),$TerminBraun);
			SetValue($this->GetIDForIdent("Blaue_Tonne"),$TerminBlau);
			SetValue($this->GetIDForIdent("Gelbe_Tonne"),$TerminGelb);
            SetValue($this->GetIDForIdent("Woche_String"),$Wochestr);
    }
    
   public function Tonnen_Morgen(){

        $Abholtage['blaue Tonne']   = GetValue($this->GetIDForIdent("Blaue_Tonne"));
        $Abholtage['graue Tonne']   = GetValue($this->GetIDForIdent("Graue_Tonne"));
        $Abholtage['gelbe Tonne']   = GetValue($this->GetIDForIdent("Gelbe_Tonne"));
        $Abholtage['braune Tonne']  = GetValue($this->GetIDForIdent("Braune_Tonne"));

	        foreach ($Abholtage as $TonneHeute)
                {
		        if (mktime(0, 0, 0, date("m") , date("d")+1, date("Y")) == $TonneHeute)
		            {
                        $WebFrontIns = $this->ReadPropertyInteger("WebFrontInstanceID");
                        if ($WebFrontIns != "")
		                WFC_PushNotification($WebFrontIns, 'Mülltonnen', 'Morgen wird die '.array_search($TonneHeute, $Abholtage).' abgeholt!', '', 0);
		            }
	            }
   }

   public function Termine($Tonne,$Datensatz)
   {
        $Termindaten = $this->GetBuffer("Termine");
        return $Termindaten[$Tonne][$Datensatz];
   }
    
   private function SetTimerWeekByName($parentID, $name, $hour,$minutes)
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
		    IPS_SetEventCyclic($eid, 3 /*  	Wöchentlich */ , 1 /* Alle X Wochen*/ ,64,/*Sonntag*/ 0,0,0);
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
            IPS_SetEventScript($eid, 'BT_Tonnen_Morgen($_IPS["TARGET"]);');
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