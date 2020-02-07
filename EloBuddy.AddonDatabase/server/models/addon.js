const mongoose = require('mongoose');

var schema = new mongoose.Schema({
  author: { type: mongoose.Schema.Types.ObjectId, ref: 'Author', required: true },
  name: { type: String, required: true },
  imageUrl: { type: String, required: true },
  topicUrl: { type: String, required: true },
  githubUrl: { type: String, required: true },
  category: { type: String, enum: ['Champion', 'Utility', 'Library'], default: 'Champion', required: true },
  champion: { type: mongoose.Schema.Types.ObjectId, ref: 'Champion' },
  utility_type: { type: String, enum: ['Utility', 'Evade', 'Awareness', 'Bot'], default: 'Utility' },
  library_type: { type: String, enum: ['Library', 'Prediction', 'Orbwalker'], default: 'Library' },
  status: { type: String, enum: ['Unknown', 'Working', 'Not working'], default: 'Unknown' },
  type: { type: String, required: true, enum: ['Free', 'Buddy-Only', 'Paid'], default: 'Free' },
  created_at: { type: Date, default: Date.now },
  likes: [ {
    hwid: { type: String, unique: true, index: true }
  } ],
  wins: [ {
    created_at: { type: Date, default: Date.now }
  } ],
  losses: [ {
    created_at: { type: Date, default: Date.now }
  } ],
});

module.exports = mongoose.model('Addon', schema);
