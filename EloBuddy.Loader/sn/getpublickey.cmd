@ECHO OFF
sn.exe -p key.snk key.PublicKey
sn.exe -tp key.PublicKey
PAUSE