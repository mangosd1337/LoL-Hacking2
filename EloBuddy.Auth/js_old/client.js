var net = require('net');
var rsa = require('node-rsa');
var crypto = require('crypto');

var SHA512_KEY = 'HelloWorld';
var key = new rsa('-----BEGIN PRIVATE KEY-----\n' +
'MIIBVAIBADANBgkqhkiG9w0BAQEFAASCAT4wggE6AgEAAkEA2LThBLzKF9ZyDC04\n' +
'eDFZhk+IdzM8S6IvdSqQmDWWNEjLSWBe/ZNRP8/qOVKwp1Qe0Mjw3wl7xSbPlZ9f\n' +
'K6xuLwIDAQABAkAjF9+cucnsZFDhwez0IeHCi1ypGQX5pZET40m5LGxfmOHpw8Lw\n' +
'wRm/KFv+zZuF3/RzDrPTIs2gH1Yy6bGH1R8BAiEA8kwr/HJaYxzc4UrcWprVbvHN\n' +
'pvDEc0G9MtYLuOF+uoECIQDk9jef0uLODIOQ/wTisFb5tMooC0vwDlo9PvCsyjLw\n' +
'rwIgR9ALuWarI3UKgjuN08zQNXG1YiU6FG8HhGmsT7+FsAECIQDExJz1K4VjUvnW\n' +
'qHOIZce5fZemZl7yhUMkE20+8d5pXQIgG251JQiWQSBIuxRUlkOs3egnQPH+XkHO\n' +
'dHz2Q6u/O9M=\n' +
'-----END PRIVATE KEY-----');

var client = net.connect ({
	port: 80
}, function ()
{
	// first 256 bytes
	var authObject =
	{
		username: 'finn',
		password: 'test',
		version: 1.0 //loader version
	};

	var signature = crypto.createHmac('sha512', SHA512_KEY).update(JSON.stringify(authObject)).digest('hex');

	// echo reponse
	var echoArray = [];
	while (echoArray.length < 10) {
		echoArray.push( Math.floor(Math.random() * (0x7FFFFFFFFFFFF - 0x800000000000) - 0x900000000000 * 2) ); // do whatever math you want, as long as those are 64 Bits numbers, and both! positive/negative
	}

	// fill numbers, total of 171 bytes in the echoArray after being stringified.
	for(var i = 0; i < echoArray.length; i++) {
		while (echoArray[i].toString().length < 16) {
			echoArray[i] += 0x100000000000;
		}
	}

	var request =
	{
		rsaAuthObject: key.encrypt(JSON.stringify(authObject), 'base64'),
		echoObject: key.encrypt(JSON.stringify(echoArray), 'base64'),
		rsaHash: signature,
		echoHash: crypto.createHmac('sha512', SHA512_KEY).update(JSON.stringify(echoArray)).digest('hex')
	};

	// hide array values
	var request = request.rsaAuthObject + request.echoObject + request.rsaHash + request.echoHash;
	client.write(request);
});

client.on('data', function (data) {
	console.log('recv:' + data);

	// add verification
});
