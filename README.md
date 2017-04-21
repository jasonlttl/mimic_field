# Mimic Fields (work in progress)

A mimic field takes its value from one or more prioritized fields on the same entity.

## Why?

Some people are writers by trade, some maintain website content as their third job.
Everyone hates duplicating content.

Given a shareable content type with teaser and image fields.
* Some will want just one set of fields.
* Some will want different ones for different spots on the site, for twitter cards, and for facebook.
* Some will always want different fields and for some they will rarely want separate fields.

This module attempts to make all of these people happy by creating a field that mimics 
the content of other fields at a low level.

For example, if a site has a highlight image we may have several fields.
* image - default for everything.
* image_social - defaults to image.
* image_twitter - defaults to social.

Then, if you want to configure the metadata module, you tell it to use
the image_twitter field and the editor can set this in one of three places
depending on whether or not it needs to be unique to twitter.

It's progressive editing.

## Opportunities

This becomes even more interesting when you add crops in that one source image
could potentially provide the output for a wide range of aspect ratios 
(4:3, 16:9, 2:1, 3:4, etc)... but it doesn't necessarily have to because
you can provide fields that target the requisite sizes exactly (but fallback).

With image fields, this can also help deduplicate content in the absence of a 
media browser since people shouldn't have to upload the same media to the same 
entity multiple times.

## Why God? Why? 

**Developer:** I've made the content type with a teaser field. Whatever you 
enter there will show up in the teaser, in facebook share text, and on 
the twitter card.
 
**Writer:** Fantastic! Hey, how do I have different text for the social media
accounts. For those I'd like it to be more of an enticement, but if they're
already on the site, I'd rather use more descriptive text.

**Developer:** Ok, I've made a separate field for the social media teaser.

**Writer:** Thanks! Can it be different for facebook and twitter? We use
different voices on the different platforms.

**Developer:** Oh, I guess that makes sense. Sure, they are now separate fields.

**New Writer:** Hey, I've filled out the teaser field and something else is
showing on the twitter cards? What's going on?

**Developer:** Well, you see... hooks vs descriptions... different voices...
 
**New Writer:** What? No, that's way to much work. I just want one field 99.9%
of the time.
