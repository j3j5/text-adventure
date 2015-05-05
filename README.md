# reply-bot
A simple Twitter bot that listen for a given string on Twitter Stream and replies to all tweets which contain it.

## Use

First of all, open the file *cli/filter_stream.php*, find the *$keywords* array and replace
there with the keywords you want to use. The current code is made as a proof of concept for
a stupid theory, where many spanish proverbs can be replaced with "kick in the balls" ("patada en los cojones") so
the bot looks for some keywords, checks with the regex given on the *$text_patterns* (the position on the array must be the same than
the position of the keyword on *$keywords* array) and if it matches, it takes the reply from the *$replies* array
defined on the callback function (**) and post that as an answer.

Once you are done with the configuration, go to the projects folder on your CLI and run

```
$ ./run-cli filter_stream KeywordToTrack1 KeywordToTrack2
```

The script will connect to the stream and start listening for the keywords and act whenever the
regex for the keyword is matched.
