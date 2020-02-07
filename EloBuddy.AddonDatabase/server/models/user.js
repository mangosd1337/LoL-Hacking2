//@flow
const mongoose = require('mongoose');
const bcrypt = require('bcrypt');
const SALT_ROUNDS = 10;

var schema = new mongoose.Schema({
  username: { type: String, required: true },
  password: { type: String, required: true }, //Encrypted
  type: { type: String, required: true, enum: ['Admin', 'Support', 'Developer'], default: 'Developer' },
  author: { type: mongoose.Schema.Types.ObjectId, ref: 'Author'}
});
schema.pre('save', function(next){
    var user = this;
    if (!user.isModified('password')){
      return next();
    }
    bcrypt.genSalt(SALT_ROUNDS, function(err, salt){
        if(err){
          return next(err);
        }
        bcrypt.hash(user.password, salt, function(err, hash){
            if(err){
              return next(err);
            }
            user.password = hash;
            next();
        });
    });
});
schema.methods.comparePassword = function(password, callback) {
  bcrypt.compare(password, this.password, callback);
}

module.exports = mongoose.model('User', schema);
