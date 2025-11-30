anime({
  targets: '#motionPath',
  strokeDashoffset: [anime.setDashoffset, 0],
  stroke: '#000',
  easing: 'linear',
  duration: 5000,
  delay: 500, // Start line drawing 500ms after car
  loop: true
});