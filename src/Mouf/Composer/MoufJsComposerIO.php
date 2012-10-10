<?php
namespace Mouf\Composer;

use  Composer\IO\IOInterface;

/**
 * An IO class to manage IOs in Mouf from Composer using the slow iframe technique and Chunked encoded HTTP requests.
 * 
 * @author David Négrier
 * @Component
 */
class MoufJsComposerIO implements IOInterface {
	/**
	 * Is this input means interactive?
	 *
	 * @return bool
	 */
	public function isInteractive() {
		return false;
	}
	
	/**
	 * Is this input verbose?
	 *
	 * @return bool
	 */
	public function isVerbose() {
		return true;
	}
	
	/**
	 * Is this output decorated?
	 *
	 * @return bool
	 */
	public function isDecorated() {
		return true;
	}
	
	/**
	 * Writes a message to the output.
	 *
	 * @param string|array $messages The message as an array of lines or a single string
	 * @param bool         $newline  Whether to add a newline or not
	 */
	public function write($messages, $newline = true) {
		if ($newline) {
			$messages.= "<br/>";
		}
		
		$msg = "<script>window.parent.Composer.consoleOutput(".json_encode($messages).")</script>";
		
		ChunckedUtils::writeChunk($msg);
	}
	
	/**
	 * Overwrites a previous message to the output.
	 *
	 * @param string|array $messages The message as an array of lines or a single string
	 * @param bool         $newline  Whether to add a newline or not
	 * @param integer      $size     The size of line
	 */
	public function overwrite($messages, $newline = true, $size = 80) {
		$this->write($messages, $newline);
	}
	
	/**
	 * Asks a question to the user.
	 *
	 * @param string|array $question The question to ask
	 * @param string       $default  The default answer if none is given by the user
	 *
	 * @return string The user answer
	 *
	 * @throws \RuntimeException If there is no data to read in the input stream
	 */
	public function ask($question, $default = null) {
		throw new \Exception("Not implemented");
	}
	
	/**
	 * Asks a confirmation to the user.
	 *
	 * The question will be asked until the user answers by nothing, yes, or no.
	 *
	 * @param string|array $question The question to ask
	 * @param bool         $default  The default answer if the user enters nothing
	 *
	 * @return bool true if the user has confirmed, false otherwise
	 */
	public function askConfirmation($question, $default = true) {
		throw new \Exception("Not implemented");
	}
	
	/**
	 * Asks for a value and validates the response.
	 *
	 * The validator receives the data to validate. It must return the
	 * validated data when the data is valid and throw an exception
	 * otherwise.
	 *
	 * @param string|array $question  The question to ask
	 * @param callback     $validator A PHP callback
	 * @param integer      $attempts  Max number of times to ask before giving up (false by default, which means infinite)
	 * @param string       $default   The default answer if none is given by the user
	 *
	 * @return mixed
	 *
	 * @throws \Exception When any of the validators return an error
	 */
	public function askAndValidate($question, $validator, $attempts = false, $default = null) {
		throw new \Exception("Not implemented");
	}
	
	/**
	 * Asks a question to the user and hide the answer.
	 *
	 * @param string $question The question to ask
	 *
	 * @return string The answer
	 */
	public function askAndHideAnswer($question) {
		throw new \Exception("Not implemented");
	}
	
	
	protected $authorizations = array();
	
	/**
     * {@inheritDoc}
     */
    public function getAuthorizations()
    {
        return $this->authorizations;
    }

    /**
     * {@inheritDoc}
     */
    public function hasAuthorization($repositoryName)
    {
        $auths = $this->getAuthorizations();

        return isset($auths[$repositoryName]);
    }

    /**
     * {@inheritDoc}
     */
    public function getAuthorization($repositoryName)
    {
        $auths = $this->getAuthorizations();

        return isset($auths[$repositoryName]) ? $auths[$repositoryName] : array('username' => null, 'password' => null);
    }

    /**
     * {@inheritDoc}
     */
    public function setAuthorization($repositoryName, $username, $password = null)
    {
        $this->authorizations[$repositoryName] = array('username' => $username, 'password' => $password);
    }
}