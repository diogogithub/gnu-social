# TODO

Load and Storage:
- Upgrade STOMP queue
- Port PEAR DB_DataObject to PDO_DataObject

Network:
- Port PEAR HTTP to Guzzle
- Port PEAR Mail to PHPSendMail
- Add OAuth2 support (deprecate OAuth1?)

General:
- Fix failling unit tests
- Improve Cronish
  - Run session garbage collection
  - Cleanup Email Registration
- Refactoring of confirmation codes
- Refactoring of Exceptions

Modules:
- Introduce new metadata for plugins (category and thumb)
- Add plugin management tool as a install step
- Allow to install remote plugins and suggest popular trusted ones
- Replace SimpleCaptcha by FacileCaptcha once the latter is ready
