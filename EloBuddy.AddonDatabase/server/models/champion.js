const mongoose = require('mongoose');

var schema = new mongoose.Schema({
  name: { type: String, unique: true, index: true },
  imageUrl: { type: String, required: true },
  category: { type: String, enum: ['Assassin', 'Fighter', 'Mage', 'Marksman', 'Support', 'Tank'], required: true },
});

module.exports = mongoose.model('Champion', schema);
