const router = require('express').Router();
function validateForm(payload) {
  if (!payload.username) {
    return {
      success: false,
      message: "Please provide a correct username."
    };
  }
  if (!payload.password) {
    return {
      success: false,
      message: "Password not provided."
    };
  }
  return {
    success: true,
    message: "Registered",
  };
}
router.post('/signup', function(req, res, next) {
  let validationResult = validateForm(req.body);
  if (!validationResult.success) {
    return res.status(400).json({ success: false, message: validationResult.message });
  }
  return res.status(200).end();
});

router.post('/login', function(req, res, next) {
  let validationResult = validateForm(req.body);
  if (!validationResult.success) {
    return res.status(400).json({ success: false, message: validationResult.message });
  }
  return res.status(200).end();
});

module.exports = router;
