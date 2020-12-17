/**
 * @package Polylang
 */

 /**
 * External dependencies
 */

const path = require( 'path' );
const glob = require( 'glob' ).sync;
const MiniCssExtractPlugin = require( 'mini-css-extract-plugin' );
const CssMinimizerPlugin = require( 'css-minimizer-webpack-plugin' );
const { CleanWebpackPlugin } = require( 'clean-webpack-plugin' );

/**
 * Internal dependencies
 */

 const getJsFileNamesEntries = require( './webpack/FileNames' );

function configureWebpack( options ){
	const mode = options.mode;
	const isProduction = mode === 'production' || false;
	console.log('Webpack mode:', mode);
	console.log('isProduction:', isProduction);
	console.log('dirname:', __dirname);

	const commonFoldersToIgnore = [
		'node_modules/**',
		'vendor/**',
		'tmp/**',
		'webpack/**'
	];

	const jsFileNamesToIgnore = [
		'*.config.js',
		'**/*.src.js',
		'**/*.min.js',
		'**/*.dep.js',
	];
	
	const jsFileNamesEntries = getJsFileNamesEntries( commonFoldersToIgnore, jsFileNamesToIgnore );

	const cssFileNames = glob( '**/*.css', { 'ignore': [ ...commonFoldersToIgnore, '**/*.min.css' ] } ).map( filename => `./${ filename }`);
	console.log( 'css files to minify:', cssFileNames );

	// Prepare webpack configuration to minify css files to source folder as target folder and suffix file name with .min.js extension.
	const cssFileNamesEntries = cssFileNames.map( ( filename ) => {
			const entry = {};
			entry[ path.parse( filename ).name ] = filename;
			const output = {
				filename: `${path.parse( filename ).dir}/[name].work`,
				path: path.resolve( __dirname ), // Output folder as project root to put files in the same folder as source files.
			}
			const config = {
				entry: entry,
				output: output,
				plugins: [
					new MiniCssExtractPlugin(
						{
							filename: `${path.parse( filename ).dir}/[name].min.css`
						}
					),
					new CleanWebpackPlugin(
						{
							dry: false,
							verbose: false,
							cleanOnceBeforeBuildPatterns: [], // Disable to clean nothing before build.
							cleanAfterEveryBuildPatterns: [
								'**/*.work',
								'**/*.LICENSE.txt'
							],
						}
					)
				],
				module: {
					rules: [
						{
							test: /\.css$/i,
							use: [ MiniCssExtractPlugin.loader, 'css-loader'],
						},
					],
				},
				devtool: ! isProduction ? 'source-map' : false,
				optimization: {
					minimize: true,
					minimizer: [
						new CssMinimizerPlugin(),
					],
				},
			};
			return config;
		},
		{}
	);

	// Make webpack configuration.
	const config = [
		...jsFileNamesEntries, // Add config for js files.
		...cssFileNamesEntries, // Add config for css files.
	];

	return config;
}

module.exports = ( env, options ) => {
	return configureWebpack( options );
}

