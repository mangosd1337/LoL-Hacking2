EloBuddy API
===================

EloBuddy Core Deploy API.

----------


Installation
-------------

1. Install NodeJS, NPM, Git, forever and coffee-script
```
apt-get install nodejs npm git
npm install -g forever coffee-script
```

2. Add the SSH Key to the Repository
```
ssh-keygen -t rsa && cat ~/.ssh/id_rsa.pub
```

3. Clone the repository
```
git clone git@github.com:EloBuddy/EloBuddy.API.git
```

4. Compile and launch the app
```
coffee --output bin --compile src
forever start bin/app.js
```


RESTful API
-------------

**Get masteries**
```
http(s)://api.elobuddy.net/masteries/{REGION}/{SUMMONER_NAME}
```

Available regions:
```
BR, EUNE, EUW, KR, LAN, LAS, NA, OCE, RU, TR
```

**Get EloBuddy Update Status for the last 5 builds for PBE/NA**
```
http(s)://api.elobuddy.net/deploy/core/ #hashes only
http(s)://api.elobuddy.net/riot/info  #last 5 builds PBE/NA
```

**Get EloBuddy Status by MD5**
```
http(s)://api.elobuddy.net/deploy/core/
http(s)://api.elobuddy.net/deploy/core/{HASH}
```
