[CustomMessages]
msbuildtools_title=MS Build Tools

msbuildtools_size=24.4 MB


[Code]
const
	msbuildtools_url = 'http://download.microsoft.com/download/E/E/D/EEDF18A8-4AED-4CE0-BEBE-70A83094FC5A/BuildTools_Full.exe';

procedure msbuildtools();
begin
      AddProduct('BuildTools_Full.exe',
        '/passive /norestart',
        CustomMessage('msbuildtools_title'),
        CustomMessage('msbuildtools_size'),
        msbuildtools_url,
        false, false);
end;