const mongoose = require('mongoose');

var schema = new mongoose.Schema({
  nickname: { type: String, required: true },
  addons: [ {
    type: mongoose.Schema.Types.ObjectId, ref: 'Addon'
  } ]
});

module.exports = mongoose.model('Author', schema);
