#!/usr/bin/env ruby

require 'nokogiri'

ibooks_home = '~/Library/Containers/com.apple.BKAgentService/Data/Documents/iBooks/Books/'

Dir.foreach(ibooks_home) do |dir|
  if File.extname(dir) == ".epub"
    dir = ibooks_home + dir
    File.open(dir + "/iTunesMetadata.plist") do |f|
      doc = Nokogiri::XML(f)
      book_name = doc.xpath('//key[text()="itemName"]/following::string').first.text
      Dir.chdir(dir)
      print "." if %x(zip -r "#{book_name}.epub" .) && %x(mv "#{book_name}.epub" ~/Downloads/)
    end
  end
end

puts "Done!"
