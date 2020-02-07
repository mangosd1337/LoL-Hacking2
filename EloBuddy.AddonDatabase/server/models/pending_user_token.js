const mongoose = require('mongoose');

var schema = new mongoose.Schema({
  token: { type: String, required: true }
});

module.exports = mongoose.model('PendingUserToken', schema);
