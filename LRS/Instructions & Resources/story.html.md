### Place the xapi-statements.js file and the xapiwrapper.min.js file in the root folder of the storyline output folder.
### Then, place this at the end of the storyline file:

```html
<link rel="stylesheet" href="html5/data/css/output.min.css" data-noprefix/>

<script type="text/javascript" src="xapiwrapper.min.js"></script>
<script type="text/javascript" src="xapi-statements.js"></script>
<script src="html5/lib/scripts/bootstrapper.min.js"></script>
</body>
</html>