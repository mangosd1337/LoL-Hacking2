var net		 		= require ('net');
var rsa				= require ('node-rsa');
var crypto 			= require('crypto');

var clients 		= [];
var SERVER_PORT 	= 80;

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

var server = net.createServer(function (socket) {
	console.log('client connected');

	socket.on ('data', function (data) {
		console.log(data.length);

		if (data.length == 1196) {
			var rsaAuthObject = data.slice(0, 256);
			var echoObject = data.toString('utf-8', 256, 940);
			var rsaAuthHash = data.slice(940, 1068);
			var rsaEchoHash = data.slice(1068, 1196);

			// decrypt phase
			var decryptedRsaAuthObject = key.decrypt(rsaAuthObject.toString(), 'utf-8');
			var decryptedEchoObject = key.decrypt(echoObject, 'utf-8');

			// verify, calculate own hashes
			var rsaAuthObjectHash = crypto.createHmac('sha512', SHA512_KEY).update(decryptedRsaAuthObject).digest('hex');
			var echoObjectHash = crypto.createHmac('sha512', SHA512_KEY).update(decryptedEchoObject).digest('hex');

			if (rsaAuthHash == rsaAuthObjectHash
				&& rsaEchoHash == echoObjectHash)
				{
					// both have not been modified, prepare response
					// for now, just use the echoNumbers and fuck around with it
					var response = [];

					var echoObjectArray = JSON.parse(decryptedEchoObject);
					for (var i = 0; i < echoObjectArray.length; i++) {
						var number = echoObjectArray[i];
						var bcsub = number - 2147483648;

						if (bcsub < 0) {
							bcsub *= 2;
							bcsub /= 8;
						} else {
							bcsub *= 8;
							bcsub /= 2;
							bcsub = bcsub ^ 32;
						}

						bcsub = bcsub * 0xDEAD * 0.32;
						response.push(bcsub);
					}

					socket.write(JSON.stringify(response) + '\r\n');
				}
		}
	});
});


server.listen(SERVER_PORT, function () {
	console.log('listening on port ' + SERVER_PORT)
});
