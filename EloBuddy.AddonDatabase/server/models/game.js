const mongoose = require('mongoose');
const EXPIRE_TIME = 60 * 60 * 20; //20 minutes

var schema = new mongoose.Schema({
  hwid: { type: String, unique: true, index: true },
  created_at: { type: Date, default: Date.now, expires: EXPIRE_TIME }
});

module.exports = mongoose.model('Game', schema);
