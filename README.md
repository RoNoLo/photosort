# Photo Sort

## Abstract

A small set of PHP console commands to sort, find duplicates and delete duplicate images.

You need PHP 7.2 and optional, but strongly recommend, the PHP extension ```imagick```.

All commands are build as symfony console commands and therefore come with a nice interface,
help and output system.

### Quickstart

You need:
- PHP 7.2
- git 
- composer

Get started by typing:

```bash
git clone https://github.com/RoNoLo/photosort.git
wget https://getcomposer.org/composer.phar (or download / install the composer.phar from https://getcomposer.org/download/) 
php composer.phar install
php bin/console
```

### Sort Command / app:sort

Help:

```bash
php bin/console help app:sort
```

Basic usage:

```bash
php bin/console app:sort -- /source/path/to/images/to/sort /root/destination/path
```

This command will crawl recursively the source path for images and will sort them by date
into the destination path. All images are put into sub-folders, which are created if needed.

All photo names will be kept the same, except duplicate names are found (see below). Also, 
no files will be deleted from the source path. Instead the command logs into a JSON file
what it has done (copied, skipped, identical).

The sorted structure will look like this. 

From:

```bash
images
   +-- IMG_FOO_111.jpg / filedate: 190301
   +-- IMG_FOO_112.jpg / filedate: 190301
   +-- IMG_FOO_113.jpg / filedate: 190301
   +-- IMG_FOO_114.jpg / filedate: 190302
   +-- IMG_FOO_115.jpg / filedate: 190302
   +-- IMG_FOO_116.jpg / filedate: 190302
   +-- PICTURE_004.jpg / filedate: 190404      
```

To:

```bash
2019
 +-- 1903
      +-- 190301
           +-- IMG_FOO_111.jpg
           +-- IMG_FOO_112.jpg
           +-- IMG_FOO_113.jpg
      +-- 190302
           +-- IMG_FOO_114.jpg
           +-- IMG_FOO_115.jpg
           +-- IMG_FOO_116.jpg
 +-- 1904
      +-- 190404
           +-- PICTURE_004.jpg      
```

#### Options

```--monthly``` will skip the sorting by day and sort them just by month, like so: 

```bash
2019
 +-- 1903
      +-- IMG_FOO_111.jpg
      +-- IMG_FOO_112.jpg
      +-- IMG_FOO_113.jpg
      +-- IMG_FOO_114.jpg
      +-- IMG_FOO_115.jpg
      +-- IMG_FOO_116.jpg
 +-- 1904
      +-- PICTURE_004.jpg      
```

```--not-rename-and-copy-duplicates``` will prevent the altering of image filenames, when a duplicate filename is found.
This will therefore prevent the copy to the new location.

```--hashs-file=/path/to/hashs/file.json``` will force the command to check every file before copy if the hash or 
signature is already known (which means the image is already somewere) and therefore is skipped. (The hashs-file can be created with the app:hash command)  

I recommend to run a tool like [jhead](http://www.sentex.net/~mwandel/jhead/) with jhead -ft **/*.jpg prior to the execution of the script. 
It will change the file changedate to the EXIF recording date, which may be more accurate than the filedate, which can be modified by filesystem operations.

#### Limitations

Currently only JPEG files are supported (simple extension check via RegEx). 

### Hash Command / app:hash

Help:

```bash
php bin/console help app:hash
```

Basic usage:

```bash
php bin/console app:hash -- /root/source/path
```

This command will crawl recursively the source path for images and will create message digests
of each file (sha1). If the PHP extension ```imagick``` is present, additionally a pixel signature
is also created (slower). This will help to find duplicate images were the EXIF data was modified
(in that case the signature is the same, but the sha1 is different).  

A JSON file is created and put, be default) into the source root directory. 
The name is ```photosort_hashmap.json```. 

#### Options

```--output-file=/file/path/hash.json``` will force the output of the result file to be written somewhere else.
If just a filename is given the output will be were the command is executed. When a full filepath
is given it will be put there. The file has to end with ```.json``` to be accepted.

```--chunk=<integer number>``` will force the maximum processing of files per execution. 
This might be useful, if the processing is generally slow (like for me over the LAN from a NAS)
and the creation of a very large hash file will take it's time. That option works hand in
hand with ```--output-file``` because it will continue a JSON file and skip all existing
files in there. Therefore if that option is used, use always the same ```--output-file``` option. 

## Limitations

Currently only JPEG files are supported (simple extension check via RegEx). 

### Merge Hash Command / app:hash-merge

Help:

```bash
php bin/console help app:hash-merge
```

Basic usage:

```bash
php bin/console app:hash-merge -- /source1/path/hash1.json /source2/path/has2.json ...
```

This command will just merge as many message digest hash files as given via agrument list. 
Every file should be created with the ```app:hash``` command. There are no content checks, just
a data merge. If there are duplicated keys (aka filepaths), the later will win. 

Additionally a new JSON file is created which contains a data map with sha1 to filepath and 
if found a signature to filepath map. This can be used by the ```app:sort``` command.

Two output files are created ```photosort_hashmap_merged.json``` and ```photosort_hashmap_duplicates_helper.json```. 

## Motivation

I use an [Synology](https://www.synology.com) NAS, which provides Apps for mobile phones.
Everybody can host it's own cloud solution to access the photos and videos everywhere. 
Because loading of directories containing 1000s of photos is slow (via outgoing upload speed) I decided to sort the photos by date.
Doing so was okay'ish for a short period of time. Two kids and up to six devices, which can make photos and record videos, later a programmatic solution was in need.
I thought this would be a nice exercise to improve my PowerShell skills, but I gave up to soon, by realizing that I could do it in PHP in one sitting.

That's what I did and the original solution (initial commit) works fine for me. 
It contains only seven self written PHP files (almost pure PHP file-operations). 
However, I'll probably update the sourcecode to libraries to have a more robust framework.

## Possible Improvements 

- run an external (?) file date processor automatically first (like exec jhead -ft **/*.jpg in source directory)
- read EXIF capture date from the image files as first option (second would be file date)
- support more file extensions
- read EXIF and rotate image if needed (would be wonderful, but I guess a re-encoding of the image will happen)
- extract dates from the filename (like WhatsApp filenames) to correctly store the files
- option to just copy and not move the images
- adding imagehash https://github.com/jenssegers/imagehash/ 
- https://content-blockchain.org/research/testing-different-image-hash-functions/
- https://github.com/JohannesBuchner/imagehash
- https://github.com/KilianB/JImageHash
- Hash-Maps merge