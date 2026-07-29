; Billard Stream — Windows Installer
; Byg med Inno Setup: iscc installer.iss

#define MyAppName "Billard Stream"
#define MyAppVersion "1.0.0"
#define MyAppPublisher "Wahl-IT Development & Research"
#define MyAppURL "https://www.wahl-it.dk/billard-stream/"

[Setup]
AppId={{A1B2C3D4-E5F6-7890-ABCD-EF1234567890}
AppName={#MyAppName}
AppVersion={#MyAppVersion}
AppPublisher={#MyAppPublisher}
AppPublisherURL={#MyAppURL}
DefaultDirName={autopf}\{#MyAppName}
DefaultGroupName={#MyAppName}
DisableProgramGroupPage=yes
OutputDir=installer
OutputBaseFilename=BillardStream-Setup-{#MyAppVersion}
Compression=lzma
SolidCompression=yes
WizardStyle=modern
PrivilegesRequired=admin
DisableWelcomePage=no

[Languages]
Name: "danish"; MessagesFile: "compiler:Languages\Danish.isl"
Name: "english"; MessagesFile: "compiler:Default.isl"

[Files]
Source: "BillardStream.exe"; DestDir: "{app}"; Flags: ignoreversion
Source: "ffmpeg.exe"; DestDir: "{app}"; Flags: ignoreversion

[Icons]
Name: "{autoprograms}\{#MyAppName}"; Filename: "{app}\BillardStream.exe"
Name: "{autodesktop}\{#MyAppName}"; Filename: "{app}\BillardStream.exe"

[Code]
// Licens validering under installation
function ValidateLicense(LicenseKey: string): Boolean;
var
  WinHttpReq: Variant;
  Url, Response: string;
begin
  Result := False;
  Url := 'https://www.wahl-it.dk/billard-stream/api/license.php?key=' + LicenseKey;
  
  try
    WinHttpReq := CreateOleObject('WinHttp.WinHttpRequest.5.1');
    WinHttpReq.Open('GET', Url, False);
    WinHttpReq.SetRequestHeader('User-Agent', 'BillardStream-Installer/1.0');
    WinHttpReq.Send('');
    Response := WinHttpReq.ResponseText;
    
    // Tjek om JSON indeholder "valid":true
    if Pos('"valid":true', Response) > 0 then
      Result := True;
  except
    Result := False;
  end;
end;

var
  LicensePage: TInputQueryWizardPage;

procedure InitializeWizard;
begin
  // Opret licens-side i installeren
  LicensePage := CreateInputQueryPage(
    wpWelcome,
    'Licensnøgle',
    'Indtast din Billard Stream licensnøgle',
    'Licensnøglen valideres online. Du modtog den da du købte abonnementet.'#13#10 +
    'Uden en gyldig licensnøgle kan installationen ikke fortsætte.');
  
  LicensePage.Add('Licensnøgle:', False);
end;

function NextButtonClick(CurPageID: Integer): Boolean;
var
  Key: string;
begin
  Result := True;
  
  if CurPageID = LicensePage.ID then
  begin
    Key := LicensePage.Values[0];
    if Key = '' then
    begin
      MsgBox('Indtast venligst din licensnøgle.', mbError, MB_OK);
      Result := False;
      Exit;
    end;
    
    if not ValidateLicense(Key) then
    begin
      MsgBox(
        'Ugyldig licensnøgle. Kontrollér at nøglen er korrekt, ' +
        'eller kontakt Wahl-IT support.', mbError, MB_OK);
      Result := False;
      Exit;
    end;
    
    // Gem licensnøgle til app'en
    SaveStringToFile(ExpandConstant('{app}\license.key'), Key, False);
  end;
end;
