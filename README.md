# TodoMove\Intercessor\Service\ServiceName Package Base
Base service repo to copy package.json, and Reader/Writer classes for new services (Todoist, Toodledo, Any.do, RTM)

* Readers should extend: `TodoMove\Intercessor\Service\AbstractReader`
* Writers should extend: `TodoMove\Intercessor\Service\AbstractWriter`

# How to use

* Fork, rename, replace 'ServiceName' with your actual service's name (`README.md`, composer.json`, `Reader.php`, `Writer.php`)
* Add logic, `git tag` then push


# Notes

All items use the `Metable` trait so can store any meta data needed for reference later on.  If you're syncing a folder to Wunderlist, and it returns its own folderid you can store that in the metadata (`$folder->meta('wunderlist-id', $id);`) for retrieval later on when you need to put projects in that folder
