module.exports = function(grunt) {

  // Project configuration.
  grunt.initConfig({
    pkg: grunt.file.readJSON('package.json'),
    uglify: {
      options: {
        banner: '/*! socin <%= grunt.template.today("yyyy-mm-dd") %> */\n'
      },
      build: {
        src: grunt.file.readJSON('themejs.json'),
        dest: '../../../public/vendor/rysocin/js/script.min.js'
      }
    }
  });

  // Load the plugin that provides the "uglify" task.
  grunt.loadNpmTasks('grunt-contrib-uglify');

  // Default task(s).
  grunt.registerTask('default', ['uglify']);

};
