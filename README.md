# selfpaste

[selfpaste](https://www.bananas-playground.net/projekt/selfpaste/) is a small self hosting paste service.

It is not the aim to replace any other well known paste service. It is an experiment and build for private use only.

This tool uses PHP [fileinfo](https://www.php.net/manual/en/intro.fileinfo.php)

    The functions in this module try to guess the content type and encoding of a file
    by looking for certain magic byte sequences at specific positions within the file.
    While this is not a bullet proof approach the heuristics used do a very good job.

It is **not really bulletproof**, but it does the job.  Everything can be manipulated to look alike something it isn't.

So, here is a friendly REMINDER:

    - Use it at own risk.
    - Don't open it up to the public
    - Check regularly what is added
    - Clean everything what you do not know
    - You provide the service by hosting it. Your are responsible for it!
    - Change your secret often.

# Why json as a response?

In cases the upload is over post_max_size the request will not "arrive".
Meaning the script does not receive enough information to work with.
In this case it returns the start page. Which is a valid HTTP 200 status response.
So the client can not only rely on the HTTP status code alone.

# Third party resources

Link shortening inspired and some code used from: https://www.jwz.org/base64-shortlinks/
