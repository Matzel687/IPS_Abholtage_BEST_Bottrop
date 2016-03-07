<?
    // Klassendefinition
class BEST_Bottrop_Muelltage extends IPSModule
{
   // Der Konstruktor des Moduls
   // Überschreibt den Standard Kontruktor von IPS
   public function __construct($InstanceID)
		{
         // Diese Zeile nicht löschen
         parent::__construct($InstanceID);
         // Selbsterstellter Code
  		 }

   public function Create()
		{
     		 // Diese Zeile nicht löschen.
    		  parent::Create();

			$this->RegisterPropertyString("Strasse", "Ernst-Wilczok-Platz");
			$this->RegisterPropertyString("Nummer", "1");
			$this->RegisterPropertyInteger("UpdateInterval", 1440 );
     		$this->RegisterTimer("Update", 0, 'BT_Update($_IPS["TARGET"]);');
            
  		 }

        // Überschreibt die intere IPS_ApplyChanges($id) Funktion
   public function ApplyChanges()
		{
         // Diese Zeile nicht löschen
         parent::ApplyChanges();

			if (($this->ReadPropertyString("Strasse") != "") AND ($this->ReadPropertyString("Nummer") != ""))
				{
                            //Variablen erstellen Wetter jetzt
                     $this->RegisterVariableString("Graue_Tonne","Graue Tonne","String",1);
                     $this->RegisterVariableString("Braune_Tonne","Braune Tonne","String",2);
                     $this->RegisterVariableString("Blaue_Tonne","Blaue Tonne","String",3);
                     $this->RegisterVariableString("Gelbe_Tonne","Gelbe Tonne","String",4);
                     $this->RegisterVariableString("Woche_String","Wochenübersicht","String",5);
		                    //Timer zeit setzen
			        $this->SetTimerInterval("Update", $this->ReadPropertyInteger("UpdateInterval")*1000*60);
                    $this->SetTimerWeekByName($this->$InstanceID,"Abholtage_Update", 23,55)
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
			if(strpos($html, "BEST Bottrop Abfuhrkalender") === false)
			{
				echo "Ungültige Adresse!";
				return;
			}

			while(true)
			{
			   //Zwischen <strong> und </strong> suchen nach Tonnen Typ (Grau, Braun, Gelb, Blau)
				$html = stristr($html,  '<strong>');
				if($html === false)
						break;
			   $html = stristr($html, '>');
				$TonnenTyp = substr($html, 22, strpos($html, "</strong>")-22);  // Tonnen Typ eintragen
				
				//Zwischen <tr><td class="b"> und </td></tr> suchen nach Abholdatum
				$html = stristr($html, '<tr><td class="b">');
				if($html === false)
					break;
				$html = stristr($html, '>');
				$Termin = substr($html, 15, strpos($html, "</td></tr>")-15);  // Abholdatum eintragen

           	$Abholtage[$TonnenTyp] = $Termin;  //Array Abholtage
			}
            
            // Datum aus dem Array Abholtage in Unix Timestamp umwandeln 
            $TerminBlau=strtotime($Abholtage['blaue Tonne'];  
            $TerminGrau=strtotime($Abholtage['graue Tonne');
            $TerminGelb=strtotime($Abholtage['gelbe Tonne']);
            $TerminBraun=strtotime($Abholtage['braune Tonne']);
            
            SetValue($this->GetIDForIdent("Woche_String"),"");
            
            /// HTML BOX Inhalt Tabbele erstellen
            $week = mktime(0,0,0, date('m'), date('d')+7, date('y')); // Datum von der Folgenden Woche
            
            $Wochestr = "<table width='100%' cellspacing='7' cellpadding='5'>";
            if ($TerminBraun <= $week)
	            $Wochestr.= "<td style='border:1px width=50px solid #3b3b4d' bgcolor='#A95A57' width='25%'>".$this->Wochentag($TerminBraun)."</td>";
            if ($TerminBlau <= $week)
	            $Wochestr.= "<td style='border:1px solid #3b3b4d' bgcolor='#1B6DB7' width='25%'>".$this->Wochentag($TerminBlau)."</td>";
            if ($TerminGelb <= $week)
                $Wochestr.= "<td style='border:1px solid #3b3b4d'  bgcolor='#F9E21B' width='25%'><font color='black'>".$this->Wochentag($TerminGelb)."</font></td>";
            if ($TerminGrau <= $week)
	            $Wochestr.= "<td style='border:1px solid #3b3b4d' bgcolor='#9E9E9E' width='25%'>".$this->Wochentag($TerminGrau)."</td>";
            $Wochestr .= "</table>";

            
			SetValue($this->GetIDForIdent("Graue_Tonne"),$Abholtage['graue Tonne']);
			SetValue($this->GetIDForIdent("Braune_Tonne"),$Abholtage['braune Tonne']);
			SetValue($this->GetIDForIdent("Blaue_Tonne"),$Abholtage['blaue Tonne']);
			SetValue($this->GetIDForIdent("Gelbe_Tonne"),$Abholtage['gelbe Tonne']);
            SetValue($this->GetIDForIdent("Woche_String"),$Wochestr);
    }
    
   public function SetTimerWeekByName($parentID, $name, $hour,$minutes)
    {
        $eid = @IPS_GetEventIDByName($name, $parentID);
        if($eid === false)
	    {
            $eid = IPS_CreateEvent(1);
            IPS_SetParent($eid, $parentID);
            IPS_SetName($eid, $name);
        }
        if ($hour === 0) 
        {
            IPS_SetEventActive($eid, false);
            return $eid;
        }
        else
	    {
		    IPS_SetEventCyclic($eid, 3 /*  	Wöchentlich */ , 1 /* Alle X Wochen*/ ,64,/*Sonntag*/ 0,0,0);
		    IPS_SetEventCyclicTimeFrom($eid, $hour, $minutes, 0);
		    IPS_SetEventActive($eid, true);
            return $eid;
	    }
    }
    
   public Function Wochentag($Tag)
    {
        $Wochentage = array("So","Mo","Di","Mi","Do","Fr","Sa");
        $Wochentag = date("N",$Tag);
        return $Wochentage[$Wochentag]." / ".date("d.m.Y",$Tag) ; 
    }

}
?>