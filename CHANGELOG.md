# Changelog

All notable changes to this project will be documented in this file, in reverse chronological order by release.

## [0.7.0] - 2018-04-26

### Changed

 - Enhance documentation (#58, thank to @coudenysj)
 - Allow private message handlers (#61)

### Fixed

 - Use `Prooph\Common\Messaging\HasMessageName` as only requirement for message_detection (#64)
 - Allow message detection with private constructors (#69)
 - Fix router configuration with `async_switch` (#65, thanks to @s-code) 


## [0.6.0] - 2017-12-30

### Added

 - Support every type of message in profiling plugins (#49)

### Changed

 - Support Symfony 4 and PHP 7.2 (#52)


## [0.5.1] - 2017-12-06

### Fixed

 - Fixed plugin registration (#47, thanks to @bl4ckbon3)
