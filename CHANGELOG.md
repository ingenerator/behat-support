# Change Log
All notable changes to this project will be documented in this file.
This project adheres to [Semantic Versioning](http://semver.org/).

## Unreleased

* Add PrintDebugTrait as a little BC shorthand for printing stuff from behat 
  contexts.
* Replace PhantomJSControllerContext with PhantomJSControllerExtension - does
  the same job, but as an extension.
* Add a set of useful extensions for bootstrapping, saving failing pages,
  providing kohana application dependencies into contexts that need them and 
  co-ordinating and injecting test data factories and repositories (which need
  to be actually implemented separately in the project for now). 
* Require Behat version 3

## 0.1.0 (2018-04-25)

* Add an element wrapper for the v4.x version of select2/select2 - see Select2v4
* Update JSEventWaiter to support `window` and `document` as element selectors
* Add MinkResourceDownloader
* Add JSEventWaiter to watch for javascript events
* Add a PhantomJSControllerContext for automatically starting / stopping phantomjs when required
* Add Spin::fn for retrying assertions
* Add DateParam::parse for parsing relative date strings from features
* Project commenced
