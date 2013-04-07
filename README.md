Perfect_Watermarks
==================

A replacement for Magento's GD2 image adapter with imagemagick.

Requirements
------------

You will need Imagemagick installed and the corresponding php extension
loaded.

Contribution
------------

Perfect_Watermarks will be developed in respect to git flow branching model.

* Please use develop for pull request.
* master will allways be the current "stable" version

Disclaimer
----------

Please note that in some case the use of imagemagick will produce
very high load. There is kind of a bug in OpenMP Library. Please
check that imagemagick isn't compiled with OpenMP extension.

Please look here:

- http://www.daniloaz.com/en/617/systems/high-cpu-load-when-converting-images-with-imagemagick/
- http://blog.dlcware.com/2010/12/imagemagick-openmp-and-really-bad-performance.html

Magento will extensivly check for enough memory to work with the image. The
extension will always assume that you will give enough memory to process all
your pictures. XHProf proofed that without the memory checking, the image
proccesing runs much faster.
