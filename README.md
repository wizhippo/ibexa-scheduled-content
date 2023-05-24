Wizhippo Scheduled Content Bundle
==================

Wizhippo Scheduled Content Bundle is an Ibexa Platform bundle to allow scheduling actions on content. 

Allows you to publish content as hidden, then create a schedule of actions.

Supported actions are:
- show
- hide
- trash

Actions on unpublished content (such as trashed) are not performed and are marked as evaluate. This means that if
content is restored, the unperformed actions will not ben run, but future actions will.

If the schedule command runs such that enough time elapses and multiple actions for the same content are pending, the
pending actions are performed in oldest to newest order. 

The command wzh:schedule-content is marked as an [ibexa.cron.job](https://github.com/ibexa/cron) and set to run every 5 minutes.

TODO:
- If action is to trash, remove schedules from the trashed content to avoid unexpected behavior when restored?  
- Instead of marking schedules as evaluated and leaving the history, delete the schedule? 

License
-------------------------------------

[License](LICENSE)
