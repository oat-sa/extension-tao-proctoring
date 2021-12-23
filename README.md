# extension-tao-proctoring
TaoProctoring extension defines the Proctor roles as well as an graphical user interface to manage deliveries in progress.

It is work in progress and will contain functionalities such as:
* Assigning test-takers to tests
* Validating test sessions
* Log cheating attempts

## Configuration options

### Feature flags
#### FEATURE_FLAG_PROCTOR_NOT_LAUNCH_PAUSE_MESSAGE

Controls whether message is not sent when test is paused.

- `"false"` – send the message. Default behavior.
- `"true"` – does not send the message.
