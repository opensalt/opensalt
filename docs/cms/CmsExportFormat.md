Data format for importing into the CMS
--------------------------------------

```
{ "nodes": [
        { "node":
            {
                "title": "title/identifier here",
                "description": "description here",
                "focus": {
                    "focus": "focus identifier here",
                    "focus_uuid": "focus uuid here",
                    "subtopic": "subtopic identifier here",
                    "topic": "topic identifier here",
                },
                "grades": [
                    "Kindergarten", "Grade 01", ...
                ],
                "standardName": "CMS Standard Name here, from standard name taxonomy",
                "uuid": "uuid of item here",
                "parent": "uuid of parent here",
                "weight": 10
            }
        }, ...
] }
```
