#dim Version[4]
#expr ParseVersion("..\EloBuddy.Loader\bin\Release\EloBuddy.Loader.exe", Version[0], Version[1], Version[2], Version[3])
#define MyAppVersion Str(Version[0]) + "." + Str(Version[1]) + "." + Str(Version[2]) + "." + Str(Version[3])
#define MyAppName "EloBuddy"
#define MyAppExeName "EloBuddy.Loader.exe"

[Setup]
AppName={#MyAppName}
AppVersion={#MyAppVersion}
AppId={#MyAppName}
DefaultDirName="{pf}\EloBuddy"
Compression=lzma2
SolidCompression=yes
DisableReadyPage=no
DisableReadyMemo=no
DisableStartupPrompt=yes
DisableFinishedPage=yes
Uninstallable=no
OutputDir="..\target\"
OutputBaseFilename=EloBuddy-Setup
PrivilegesRequired=admin

[Files]
;Loader
Source: "..\target\EloBuddy.Loader.exe"; DestName: "EloBuddy.Loader.exe"; Excludes: *.vshost.exe; DestDir: {app}; Flags: ignoreversion
Source: "..\target\EloBuddy.Loader.exe.config"; DestName: "EloBuddy.Loader.exe.config"; Excludes: *.vshost.exe.config;  DestDir: {app}; Flags: ignoreversion
Source: "..\target\System\*.dll"; DestDir: "{app}\System\"; Flags: ignoreversion
Source: "..\target\Dependencies\*.dll"; DestDir: "{app}\Dependencies\"; Flags: ignoreversion

[Icons]
Name: "{commondesktop}\{#MyAppName}"; Filename: "{app}\{#MyAppExeName}"; WorkingDir: "{app}"
Name: "{commondesktop}\Visit {#MyAppName} Website"; Filename: "http://elobuddy.net/"

[Languages]
Name: "en"; MessagesFile: "compiler:Default.isl"
Name: "de"; MessagesFile: "compiler:Languages\German.isl"



;Dependencies
#include "Scripts\products.iss"
#include "Scripts\products\stringversion.iss"
#include "Scripts\products\winversion.iss"
#include "Scripts\products\fileversion.iss"
#include "Scripts\products\dotnetfxversion.iss"
#include "Scripts\products\dotnetfx40full.iss"
#include "Scripts\products\dotnetfx45.iss"
#include "Scripts\products\msbuildtools.iss"
#include "Scripts\products\directx.iss"

[Run]
Filename: {app}\{#MyAppExeName}; Flags: shellexec nowait; 

[Code]
function InitializeSetup(): Boolean;
begin
	initwinversion();
  dotnetfx40full();
  dotnetfx45(1);
  msbuildtools();
  installDirectx();
	Result := true;
end;
