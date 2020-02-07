:: Defining some variables
set RepoFolder=C:\Users\Administrator\Desktop\EloBuddy.Dependencies Repo
set WorkspaceFolder=C:\Users\Administrator\.jenkins\jobs\EloBuddy.Loader\workspace
set InnoFolder=C:\Program Files (x86)\Inno Setup 5

cd %WorkspaceFolder%

:: Remove old folder and create a new one
IF EXIST "target" (
    rmdir "target" /s /q
)
mkdir "target"
mkdir "target\Dependencies"
mkdir "target\System"

:: Copy main executeables to the folder
copy "EloBuddy.Loader\bin\Release\Confused\EloBuddy.Loader.exe" "target"
copy "EloBuddy.Loader\bin\Release\EloBuddy.Loader.exe.config" "target"

:: Copy dependencies to the folder
copy "EloBuddy.Unmanaged\Release\EloBuddy.Unmanaged.dll" "target\Dependencies" 
copy "Dependencies\LibGit2Sharp.dll" "target\Dependencies"
copy "Dependencies\NativeBinaries\32bit\git2-6b36945.dll" "target\Dependencies"
copy "Dependencies\NLog.dll" "target\Dependencies"
copy "Dependencies\LogEntriesCore.dll" "target\Dependencies"
copy "Dependencies\LogEntriesNLog.dll" "target\Dependencies"
copy "C:\Users\Administrator\Desktop\Loader Static System\Newtonsoft.Json.dll" "target\Dependencies"
copy "C:\Users\Administrator\Desktop\Loader Static Dependencies\*.dll" "target\Dependencies"

:: Copy system files to the folder
copy "C:\Users\Administrator\.jenkins\jobs\EloBuddy.Sandbox\workspace\EloBuddy.Sandbox\bin\Release\Confused\EloBuddy.Sandbox.dll" "target\System"
copy "C:\Users\Administrator\.jenkins\jobs\EloBuddy.SDK\workspace\EloBuddy.SDK\bin\Release\Confused\EloBuddy.SDK.dll" "target\System"
copy "C:\Users\Administrator\Desktop\Loader Static System\*.dll" "target\System"
copy "C:\Users\Administrator\.jenkins\jobs\EloBuddy.Networking\workspace\bin\Release\Confused\EloBuddy.Networking.dll" "target\Dependencies"

:: Build the zip file
cd "target"
7z.exe a -tzip "EloBuddy.Loader.Complete.zip" *

:: Build the setup file
"%InnoFolder%\compil32.exe" /cc "..\InnoSetup\EloBuddy setup.iss"

:: Sign Setup
set DESKTOP=C:\Users\Administrator\Desktop
set BINPATH=C:\Users\Administrator\.jenkins\jobs\EloBuddy.Loader\workspace\EloBuddy.Loader\bin\Release\Confused\EloBuddy.Loader.exe
set SIGNTOOL=C:\Program Files (x86)\Windows Kits\8.1\bin\x86

"%SIGNTOOL%"\signtool.exe sign /f "%DESKTOP%\HOSTPLANET_CERT.p12" /p "G4hKCs3Ye4Sm3Fh7DU33P8nUSb3Sh8" /v /t http://timestamp.comodoca.com/authenticode EloBuddy-Setup.exe

:: Mess up the folder structure
mkdir "Loader"
move "EloBuddy.Loader.exe" "Loader"
move "EloBuddy.Loader.exe.config" "Loader"
move "EloBuddy.Loader.Complete.zip" "Loader"
mkdir "Setup"
move "EloBuddy-Setup.exe" "Setup"

:: Update the repo folder
cd %RepoFolder%
cd "C:\Users\Administrator\Desktop\EloBuddy.Dependencies Repo"
git remote set-url origin https://EloBuddyLtd:pzERggYdNewOfpoNwTIHOgmxeQiNzmLIGaJAVucyIMOGbZKtUOHecXp@github.com/EloBuddy/EloBuddy.Dependencies.git
git pull

:: Generate new json values
cd %WorkspaceFolder%
DependenciesHelper.exe "target" "C:\Users\Administrator\Desktop\EloBuddy.Dependencies Repo\dependencies.json"

:: Copy all content to the repo folder
xcopy "target" "C:\Users\Administrator\Desktop\EloBuddy.Dependencies Repo" /s /e /y

:: Publish the changes to GitHub
cd %RepoFolder%
git add .
git commit -m "Automatic update of dependencies"
git push origin master

:: Restore old folder structure
cd %WorkspaceFolder%\target
move "Loader\EloBuddy.Loader.exe" "%cd%"
move "Loader\EloBuddy.Loader.exe.config" "%cd%"
move "Loader\EloBuddy.Loader.Complete.zip" "%cd%"
rmdir "Loader"
move "Setup\EloBuddy-Setup.exe" "%cd%"
rmdir "Setup"