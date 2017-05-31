OpenSALT "Chooser Mode"
=======================
OpenSALT can be used as a component embedded in an iframe within any other web application, allowing a user to browse the tree structure of a CASE competency framework and choose a CF Item from the framework for further processing in the enclosing application.  The following is a recipe for using this "chooser mode" of OpenSALT.  

1. Assume that:
	- the domain of the OpenSALT instance is `https://opensalt.company.com/`
	- the domain of the enclosing web application (in which OpenSALT will be embedded) is `https://learningapp.company.com/`
2. Include an iframe in the enclosing application, setting the src of the iframe to point to an OpenSALT instance using one of the following two url forms:
    - For a CF document that is stored directly in the database for `opensalt.company.com`:
        - `https://opensalt.company.com/cftree/doc/XXX?mode=chooser&choosercallbackurl=YYY`
        - Where `XXX` is the integer document id for the CF document (e.g. `32`), and `YYY` is a callback URL on the `learningapp.company.com` domain (see below for what this callback URL might look like).
    - For a CF document that is being ultimately served from a different CASE server (OpenSALT or otherwise):
        - `https://opensalt.company.com/cftree/remote?url=ZZZ&mode=chooser&choosercallbackurl=YYY`
        - Where `ZZZ` is CASE url for the CF document (e.g. `http://opensalt.opened.com/ims/case/v1p0/CFPackages/b821b70d-d46c-519b-b5cc-ca2260fc31f8`), and `YYY` is the callback URL.
3. OpenSALT will open in the iframe running in "chooser mode", meaning that:
	- The "standalone" OpenSALT header and footer are hidden.
	- Other interface elements, such as the document title, sign in button, buttons to toggle between "Tree View" and "Association View", and the right-side tree are also hidden.
	- The only thing left showing is the document tree of the specified document.
	- When the user clicks on an item in the tree, they will see a magnifying glass button and a "Choose" button.
	- Clicking the mangifying glass will show the details for the item (which slide onto the top of the iframe, "above" the tree).
	- Clicking the "Choose" button will choose that item for further processing by your web app.
4. When the user clicks "Choose", OpenSALT will redirect from within the iframe to the callback URL specified (as `ZZZ` in the sample urls above) in the original iframe src.  
	- For example, the callback URL might be `https://learningapp.company.com/service/opensalt_callback`.
	- OpenSALT will include the following JSON data for the chosen CF Item in the query (GET) portion of the callback URL:
	```sh
	"item": {
		"identifier",	// the CASE GUID for this item
		"saltId",		// the OpenSALT database id for the item (if the framework is served via OpenSALT)
		"fullStatement",
		"abbreviatedStatement",
		"humanCodingScheme",
		"listEnumInSource",
		"conceptKeywords",
		"conceptKeywordsURI",
		"notes",
		"language",
		"educationalAlignment",
		"itemType",
		"lastChangeDateTime"
	}
	```
	- Note that if any of the above attributes are empty, the empty attributes will not be sent. You are only guaranteed to get an identifier and a fullStatement.
	- So, for example, the full, encoded url that gets redirected to in the iframe might be:
		`https://learningapp.company.com/service/opensalt_callback?data=%7B%22item%22%3A%7B%22identifier%22%3A%22b9a05c97-314d-5565-b196-933ffc54ba94%22%2C%22saltId%22%3A344%2C%22fullStatement%22%3A%22Reading%20Standards%20for%20Literacy%20in%20Science%20and%20Technical%20Subjects%206%E2%80%9412%22%2C%22listEnumInSource%22%3A%224%22%2C%22language%22%3A%22en%22%2C%22educationalAlignment%22%3A%2206%2C07%2C08%2C09%2C10%2C11%2C12%22%2C%22lastChangeDateTime%22%3A%222017-01-19T02%3A24%3A49%22%7D%7D`
5. You are free to do whatever you want with this data in your web application. One way to handle things is as follows:
	- Have your callback service write out the received item data in a javascript function call referencing the parent window.  Here's how that might be done in a simple Symfony routing function:
	```sh
	$data = $request->query->get("data");
	echo "<script>parent.opensaltCallback($data);</script>";
	die();
	```
	- Then define the javascript function in the web application to receive and do something with the information, e.g.:
	```sh
	function opensaltCallback(data) {
		alert("The chosen item was '" + data.item.fullStatement + "'");
	}
	```
6. Note that it is up to you to provide a means for users in your web application to choose which CF package to choose from.
