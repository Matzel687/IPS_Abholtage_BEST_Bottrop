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

			$this->RegisterPropertyString("Straße", "Ernst-Wilczok-Platz");
			$this->RegisterPropertyString("Nummer", "1");
			$this->RegisterPropertyInteger("UpdateInterval", 1440 );
     		$this->RegisterTimer("Update", 0, 'BT_Update($_IPS["TARGET"]);');
            
  		 }

        // Überschreibt die intere IPS_ApplyChanges($id) Funktion
   public function ApplyChanges()
		{
         // Diese Zeile nicht löschen
         parent::ApplyChanges();

			if (($this->ReadPropertyString("Straße") != "") AND ($this->ReadPropertyString("Nummer") != ""))
				{
                            //Variablen erstellen Wetter jetzt
                     $this->RegisterVariableString("Graue_Tonne","Graue Tonne","String",1);
                     $this->RegisterVariableString("Braune_Tonne","Graue Tonne","String",2);
                     $this->RegisterVariableString("Blaue_Tonne","Graue Tonne","String",3);
                     $this->RegisterVariableString("Gelbe_Tonne","Graue Tonne","String",4);
		                    //Timer zeit setzen
			        $this->SetTimerInterval("Update", $this->ReadPropertyInteger("UpdateInterval")*1000*60);
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

            $Straße =  $this->ReadPropertyString("Straße");  // Straßenname
            $Nummer = $this->ReadPropertyString("Nummer");  // Hausnummer

			$html = file_get_contents("http://www.best-bottrop.de/abfallkalender/start/uebersicht?strassenname=".$Straße."&hausnummer=".$Nummer);
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

			SetValue($this->GetIDForIdent("Graue_Tonne"),$Abholtage['graue Tonne']);
			SetValue($this->GetIDForIdent("Braune_Tonne"),$Abholtage['braune Tonne']);
			SetValue($this->GetIDForIdent("Blaue_Tonne"),$Abholtage['blaue Tonne']);
			SetValue($this->GetIDForIdent("Gelbe_Tonne"),$Abholtage['gelbe Tonne']);
    }
}
?>