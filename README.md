# Photo Sort

## Abstract

This is a console PHP script, which can sort images from a source directory into a destination directory-structure.
All photos will be sorted by date, thus each photo is sorted into a directory which represents only one day of the year.
All photos of that day will be stored there.

The structure looks like this:

```
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
All photo names will be kept the same.

I recommend to run a tool like [jhead](http://www.sentex.net/~mwandel/jhead/) with jhead -ft **/*.jpg prior to the execution of the script. 
It will change the file changedate to the EXIF recording date, which may be more accurate than the filedate, which can be modified by filesystem operations.

## Use

```bash
cli/photosort.php --source /path/of/source/with/images --destination /root/of/photo/catalog
```

This will take all photos recursively from the source folder and copy/move them into the destination folder.
The script will use the filedate to find the correct folder structure to move the image. 
The script will create all needed folder, checks if an image already exists and if so will do a checksum (sha1) to check if the photo is the same.
The script will check if an image already exists with another filename, if the directory already existed.

Logging is done to the console.

## Limitations

Currently only JPEG files are supported (simple extension check via RegEx). 

## Motivation

I use an [Synology](https://www.synology.com) NAS, which provides Apps for mobile phones.
Everybody can host it's own cloud solution to access the photos and videos everywhere. 
Because loading of directories containing 1000s of photos is slow (via outgoing upload speed) I decided to sort the photos by date.
Doing so was okay'ish for a short period of time. Two kids and up to six devices, which can make photos and record videos, later a programmatic solution was in need.
I thought this would be a nice exercise to improve my PowerShell skills, but I gave up to soon, by realizing that I could do it in PHP in one sitting.

That's what I did and the original solution (initial commit) works fine for me. 
It contains only seven self written PHP files (almost pure PHP file-operations). 
However, I'll probably update the sourcecode to libraries to have a more robust framework.

## Decision Workflow

Let's assume the following bash call: photosort.php --source /foo --destination /home/myimages

```
[Source image is: foo_bar.jpg with date 2019-04-03]
   |
<Is there a destination year directory? (like /home/myimages/2019)>
   |       |
 (Yes?)  (No?)-> [Create directory /home/myimages/2019/1904/190403 and move file]
   |
<Is there a destination year/month directory? (like /home/myimages/2019/1904)>
   |       |
 (Yes?)  (No?)-> [Create directory /home/myimages/2019/1904/190403 and move file]
   |
<Are there any subdirectories at /home/myimages/2019/1904?>   
   |       |
 (Yes?)  (No?)-> [Create directory /home/myimages/2019/1904/190403 and move file]
   |
[Get a list of all subdirectories at /home/myimages/2019/1904]
   |
[Filter the list by expected date]
   |
<Are there still destination directory candidates like ../190403 or ../190403_1?>
   |       |
 (Yes?)  (No?)-> [Not coded at the moment]
   |
[Create an SHA1 of the source image file]
   |
[Cycle through all destination directory candidates and do an SHA1 compare if the image is already there]
   |
<Was the image found?>
   |       |
 (No?)  (Yes?)-> [Delete the source image file]
   |
[Create directory /home/myimages/2019/1904/190403 (if needed) and move file]
   | 
[End. The source file is deleted at this point and the next file is processed until source directory is empty]
```

The file moving is extra save, to check before and after the copy / move process if the image exists at the source and destination location.
If after the copy the image will not be readable, the source is not deleted and a console log message is printed.

## Possible Improvements 

- run an external (?) file date processor automatically first (like exec jhead -ft **/*.jpg in source directory)
- read EXIF capture date from the image files as first option (second would be file date)
- support more file extensions
- read EXIF and rotate image if needed (would be wonderful, but I guess a re-encoding of the image will happen)
- extract dates from the filename (like WhatsApp filenames) to correctly store the files
- option to just copy and not move the images
- adding imagehash https://github.com/jenssegers/imagehash/ https://content-blockchain.org/research/testing-different-image-hash-functions/