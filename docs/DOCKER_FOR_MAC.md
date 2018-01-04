Docker for Mac notes
--------------------

[Docker for Mac](https://docs.docker.com/docker-for-mac/) is one of the tools that can be used on a Mac computer to run docker containers in order to do development.

The biggest issue with Docker for Mac is that it currently has very slow filesystem access.

Some possible remedies to help with the slowness issue are:

1. Use a Linux machine for development ;-)

2. Use [Docker Toolbox](https://github.com/docker/toolbox/releases/) instead.
  - Toolbox uses Virtualbox instead of Xhyve as the VM
  - The file system access via Virtualbox's shared folders is somewhat faster than the osxfs driver in Xhyve, but it is often suggested to use nfs mounts to make the filesystem access faster

3. Put everything into docker volumes
  - These can either be build statically or continuously updated via something like lsyncd
  - Random tests have shown about a 100x improvement in running SALT in development mode (~60ms vs ~6s for the */app_dev.php/lsdoc/* page)
  - This requires either a build process to be run after changes to the code or a file monitor/copy system
    - A build process adds a step that adds time before testing a change which would negate much of the speed advantage for multiple small changes tested after each change
    - A file monitor/copy system (like lsyncd) adds complexity for orchestrating the initial setup (cannot easily tell when the initial full copy is complete before moving to the next step) and adds a delay (small, but without an easy way to know it is done) after each change before it can be tested
    - Reorganising the directory structure can help optimise the docker volumes required

4. Add nfs mounts to Docker for Mac with something like [d4m-nfs](https://github.com/IFSight/d4m-nfs)
  - Random tests have shown about a 10x improvement in running SALT in development mode (~700ms vs ~6s for the */app_dev.php/lsdoc/* page)
  - The `/etc/exports` file needs to map to the root user instead of the normal user for applications like mysql to be able to chown files (otherwise those applications break), so use the following instead of the defaults:
    ```
# d4m-nfs exports
"/Users" -alldirs -mapall=0:0 localhost
"/Volumes" -alldirs -mapall=0:0 localhost
"/private" -alldirs -mapall=0:0 localhost
    ```
  - The `d4m-nfs/etc/d4m-nfs-mounts.txt` file also has to be adjusted to look like:
    ```
# Be sure that any mounts that have been added here
# have been removed from Docker for Mac -> Preferences -> File Sharing
#
# You must supply the Mac source directory and Moby VM destination directory,
# and optionally add on user/group mapping:
#
# https://developer.apple.com/legacy/library/documentation/Darwin/Reference/ManPages/man5/exports.5.html
#
# <MAC_SRD_DIR>:<MOBY_VM_DST_DIR>[:MAC_UID_MAP][:MAC_GID_MAP]
#
/Users:/Users:0:0
/Volumes:/Volumes:0:0
/private:/private:0:0
    ```
  - The d4m-nfs.sh script needs to be re-run each time Docker for Mac is restarted
