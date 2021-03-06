const fs = require( 'fs' );

/**
 * Removes some noise that exists in the testing framework error messages.
 * @param {string} msg Error message thrown by testing framework.
 * @returns {string}
 */
export const cleanErrorMessage = ( msg ) => {
	return msg
		.replace( 'expect(received).toPassAxeTests(expected)', '' )
		.replace( 'Expected page to pass Axe accessibility tests.', '' )
		.replace( /^\s*$(?:\r\n?|\n)/, '\n' );
};

/**
 * Joins and appends lines to log file
 * @param {array} lines
 */
const appendToLog = ( command, lines ) => {
	const path = `../../logs/ui-check-${command}.txt`;

	fs.appendFileSync( path, [ '\n\n', ...lines ].join( '\n' ), {
		encoding: 'utf8',
	} );
};

/**
 * Prints a message
 * @param {string} command The name of the command that matches the `core` library messaging commands.
 * @param {string[]} lines The content to print.
 */
export const printMessage = ( command, lines ) => {
	appendToLog( command, lines );
};

/**
 *
 * @param {string} type Message type. Ie: errors, warnings, info
 * @param {string|string[]} message Messages to display
 * @param {function} testToRun The test that will be executed
 */
const expectWithMessage = ( type, message, testToRun ) => {
	const output = Array.isArray( message ) ? message : [ message ];

	try {
		testToRun();
	} catch ( e ) {
		printMessage( type, output );
	}
};

/**
 * Outputs messages as error
 * @param {string|string[]} message Messages to output
 * @param {function} test Function to run
 */
export const errorWithMessageOnFail = ( message, test ) => {
	return expectWithMessage( 'errors', message, test );
};

/**
 * Outputs messages as warning
 * @param {string|string[]} message Messages to output
 * @param {function} test Function to run
 */
export const warnWithMessageOnFail = ( message, test ) => {
	return expectWithMessage( 'warnings', message, test );
};
