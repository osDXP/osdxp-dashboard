const fs  = require('fs');
const gulp = require('gulp');
const postcss = require('gulp-postcss');
const autoprefixer = require('autoprefixer');
const babel = require('gulp-babel');
const concat = require('gulp-concat');
const gulpif = require('gulp-if');
const rename = require('gulp-rename');
const sourcemaps = require('gulp-sourcemaps');
const uglify = require('gulp-uglify');
const cleanCSS = require('gulp-clean-css');
const log = require('gulplog');
const mode = require('gulp-mode')({
  modes: ["production", "development"],
  default: "development",
  verbose: false
});
let sass = require('gulp-sass');
sass.compiler = require('node-sass');

// Project paths.
const paths = {
  styles: {
    src: [
      'sass/style-admin.scss',
      'sass/style-wp-admin.scss',
      'sass/admin-bar.scss',
      'sass/wp-admin-bar.scss'
    ],
    dest: '../build',
    sass: 'sass/**/*.scss'
  },
  scripts: {
    src: ['js/**/*.js'],
    concat: 'app.js',
    dest: '../build',
    watch: ['js/**/*.js']
  },
  folders: {
    'assets': 'assets/'
  },
};

const isProduction = mode.production();


/**
 * Return the styles pipes for an asset from the list.
 *
 * @param  {string} slug             The asset slug.
 * @param  {array} assetList         The folders list.
 * @param  {boolean} forceProduction If the scripts must be forced  to be build as production.
 * @return {stream}                  The gulp stream.
 */
function makeStylePipes(slug, assetList, forceProduction) {
  const sources = [];
  paths.styles.src.forEach((src) => {
    const filePath = assetList[slug] + src;

    if (fs.existsSync(filePath)) {
      sources.push(filePath);
    }
  });

  if (sources.length === 0) return;

  log.info(`Building ${slug} Styles...`);

  return gulp.src(sources)
    // Init the sourcemaps.
    .pipe(gulpif(!isProduction, sourcemaps.init()))
    // Compile the sass.
    .pipe(sass().on('error', sass.logError))
    // Add prefixes.
    .pipe(postcss([
      autoprefixer()
    ]))
    // If not in debug mode minify the styles.
    .pipe(gulpif(isProduction || forceProduction, cleanCSS()))
    .pipe(gulpif(isProduction || forceProduction, rename({suffix: '.min'})))
    // Stop listening and write the sourcemaps.
    .pipe(gulpif(!isProduction, sourcemaps.write('.')))
    // Spit it out in the dest folder.
    .pipe(gulp.dest(assetList[slug] + paths.styles.dest)).on('end', () => { 
      log.info(`Finished '${slug}' Styles Build.`); 
    });
}


/**
 * Return the scripts pipes for an asset from the list.
 *
 * @param  {string} slug             The asset slug.
 * @param  {array} assetList         The folders list.
 * @param  {boolean} forceProduction If the scripts must be forced  to be build as production.
 * @return {stream}                  The gulp stream.
 */
function makeScriptsPipes(slug, assetList, forceProduction) {
  const sources = [];
  paths.scripts.src.forEach((src) => {
    const filePath = assetList[slug] + src;

    if (fs.existsSync(filePath) || filePath.indexOf('*') !== -1) {
      sources.push(filePath);
    }
  });

  if (sources.length === 0) return;

  log.info(`Building ${slug} Scripts...`);

  let babelOptions = {
    presets: ['airbnb']
  }
  
  // If production remove console.log, console.error.
  if (isProduction) {
    babelOptions.plugins = ['transform-remove-console'];
  }

  return gulp.src(sources)
    .pipe(gulpif(!isProduction, sourcemaps.init()))
    .pipe(concat('app.js'))
    .pipe(babel(babelOptions))
    .pipe(gulpif(isProduction || forceProduction, uglify()))
    .pipe(gulpif(isProduction || forceProduction, rename({suffix: '.min'})))
    .pipe(gulpif(!isProduction, sourcemaps.write('.')))
    // Spit it out in the dest folder.
    .pipe(gulp.dest(assetList[slug] + paths.scripts.dest)).on('end', () => { 
      log.info(`Finished '${slug}' Scripts Build.`); 
    });
}

/**
 * Watch everything.
 *
 * @param  {boolean} forceProduction If all the watched files must be forced to be build as production.
 */
function watch(forceProduction) {
  // Watch every folder STYLES.
  Object.keys(paths.folders).forEach((folder) => {
    // Watch all the folder sass files.
    gulp.watch(paths.folders[folder] + paths.styles.sass, function watchStyle() {
      if (forceProduction) production();
      return makeStylePipes(folder, paths.folders);
    });
  });

  // Watch every folder SCRIPTS.
  Object.keys(paths.folders).forEach((folder) => {
    // Watch the folder js files.
    gulp.watch(paths.folders[folder] + paths.scripts.watch, function watchScript() {
      if (forceProduction) production();
      return makeScriptsPipes(folder, paths.folders);
    });
  });
}

/**
 * Build everything.
 */
function build() {
  // Build every folder STYLES.
  Object.keys(paths.folders).forEach((folder) => {
    return makeStylePipes(folder, paths.folders);
  });

  // Build every folder SCRIPTS.
  Object.keys(paths.folders).forEach((folder) => {
    return makeScriptsPipes(folder, paths.folders);
  });
}

/**
 * Production everything.
 */
function production() {
  // Build for production every folder STYLES.
  Object.keys(paths.folders).forEach((folder) => {
    return makeStylePipes(folder, paths.folders, true);
  });

  // Build for production every folder SCRIPTS.
  Object.keys(paths.folders).forEach((folder) => {
    return makeScriptsPipes(folder, paths.folders, true);
  });
}

gulp.task('watch', function() {
  watch();
});
gulp.task('watch-prod', function() {
  watch(true);
});
gulp.task('build', async function() {
  build();
});
