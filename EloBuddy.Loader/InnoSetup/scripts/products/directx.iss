[CustomMessages]
directx_title=Directx Web-End User

directx_size=300KB - 90MB


[Code]
const
	directx_url = 'http://raw.githubusercontent.com/EloBuddy/EloBuddy.Dependencies/master/DirectX/DirectX_Installer.exe';

procedure installDirectx();
begin
      AddProduct('DirectX_Installer.exe',
        '',
        CustomMessage('directx_title'),
        CustomMessage('directx_size'),
        directx_url,
        false, false);
end;
