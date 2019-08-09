author marcan

Testing done using a Canon EOS 600D and a Canon EF-S18-55mm f/3.5-5.6 IS II.

## Pinout

1. VBAT
2. DET (common with P-GND on lens side)
3. P-GND
4. VDD
5. DCL
6. DLC
7. LCLK
8. D_GND

## Signal levels

VDD is nominally 5V. VBAT seems to also be ~5V (others report 6V).

VDD is always active (even with the camera off, if it has been powered on at
least once) when a lens is connected (DET = low). When the camera turns on, VDD
increases slightly (standby power vs. active regulator?)

The data lines are idle high and weakly pulled up by the camera. However, the
drivers are not open drain (to get better rise times).

The signal levels are 5V. Driving 3.3V on DCL does *not* work - you need 5V,
the lens seems to consider 3.3V as logic low.
3.3V on LCLK does seem to work.

## Timing

The protocol is basically a variant of SPI with CPOL=1,CPHA=1. There is no
framing or CS signal, which complicates parsing, and there is an additional
variable length ACK clock pulse driven by the lens (kind of like I²C clock
stretching).

Default clock period is 13µs (6.5µs per half cycle) = 77 kHz (80 kHz).
This decreases to 2µs for newer lenses (1µs per half cycle) = 500 kHz
Intended duty cycle seems to be 50% (fast mode seems to be 0.8µs low / 1.2µs
high, but this is likely just an analog effect).

Power-on timing:
- 1µs low glitch on DCL (?)
- 42µs delay (?)
- Single 6.5µs low pulse on DCL (does this serve a purpose?)
- 103µs delay
- First three byte cycles:
    - `C 00 L ff [TX 89us ACK 14us BUSY 2165us]` <- note long BUSY period
    - `C 0a L aa [TX 89us ACK 14us BUSY 4us]`
    - `C 00 L aa [TX 89us ACK 14us BUSY 4us]` <- VBAT turned on by camera *during* the lens ACK/BUSY pulse

Command byte timing:
- Camera drives DCL at a random value for ~18µs (slow mode) or ~4µs (fast mode)
  - This seems to usually be whatever the last bit of the previous command was,
    but not always consistent.
- Each bit is driven (by both camera and lens) on the falling edge of LCLK,
  and sampled on the rising edge.
- Camera drives DCL high (or stops driving?) and stops driving LCLK 1µs
  after the last rising edge of LCLK (this is half a cycle in fast mode, but
  much shorter than half a cycle in slow mode).
- Lens releases DLC ~4.3µs after the last rising edge of LCLK
- Lens drives LCLK low ~14µs after the last rising edge of LCLK (ACK/BUSY)
- ACK/BUSY pulse seems to last at least 4µs but may be much longer
- Gap between bytes (rising edge of LCLK ACK/BUSY to first falling edge of LCLK)
  is at least 120µs in slow mode and at least 18µs in fast mode.

Note that slow/fast mode only changes the camera timings. The lens always
behaves the same way.

## Exceptional states

Holding LCLK low for >700µs seems to reset the lens. The lens will then itself
hold LCLK low until ready (~300us, sometimes longer).

Pulsing LCLK low (e.g. shifting in one bit) then waiting causes the lens to time
out. After 740µs, it will reset itself and hold LCLK low for ~300-400µs. This
may effectively be the same situation as the above (holding LCLK low).

Command 08 powers down the lens. After this, the next command sent by the camera
is ignored, and the lens will hold LCLK low for ~2ms during that command as it
powers up.

## General command structure

Lens commands opcodes are one byte, and may have a number of arguments and reply
bytes. The lens replies to a command starting on the next byte cycle. Commands may be
pipelined: the last response byte from the lens may be transferred at the same
time as the next command from the camera.

When the lens receives an unknown command, it will echo it back to the camera
on the next cycle.

There is no explicit framing, therefore keeping sync with the protocol *requires*
knowledge of all command lengths. There are no implicit timeouts. You can send
a command, wait one second, then send the argument, and it will be interpreted
as such, not a new command. Commands have both fixed argument and response byte
counts (i.e. a fixed overall length); the camera cannot interrupt an information
retrieval command by sending another command. The lens ignores dummy data from
the camera while it is replying to a command. Canon cameras send 00 as dummy
padding bytes during such read cycles.

## Command ranges

The command codes follow certain patterns:

* 00-0f: commands 0-f with zero arguments
* 10-1f: commands 0-f with one argument
* 20-2f: commands 0-f with one dummy argument (unused/redundant)
* 30-3f: information commands
* 40-4f: commands 0-f with two arguments
* 50-5f: commands 0-f with one dummy argument (unused/redundant)
* 60-6f: more info commands
* 70-7f: unused/unimplemented
* 80-ff: largely more info commands

These range 0x, 1x, 2x, 4x, 5x encode the same operations. Each operation is
intended to be used only with one argument count (of 0x, 1x, 4x); if used with
the wrong count, it will instead re-use the argument from the previous valid
operation. The basic commands are:

```
00          No-op
01          
12 XX       Change aperture (+/- int8 0xXX)
13 XX       Same as 12
44 HH LL    Change focus (+/- int16 0xHHLL)
05          Focus to max (infinity)
06          Focus to min
07          
08          Power down
09          
0a          Ready/sync (replies aa)
0b          
0c          
0d          
0e          
0f          
```

So, for example, the focus is intended to be set with 44 HH LL, but 04, 14 XX,
24 XX, 54 XX will all just repeat whatever the previous valid 44 HH LL command
was.

## Command list

This table exhaustively lists all commands and what they return on this lens.
The lengths can be used to build a table for a protocol analyzer (or a modchip).
The busy time may help determine which commands are unimplemented/no-ops and
which actually do something.

Testing note: "--" values are presumed don't care, but actual testing was done
sending 0a bytes after every command (to determine when it gets interpreted as
command 0a and returns aa).

Consider busy timing values to have +/-1µs jitter.

```
CMD         RET         TIME        Brief description
00          00          4           No-op (padding to get last response)
01          01          4
02          02          9+          (Change aperture, repeats cmd 12/13)
03          03          10+         (Change aperture, repeats cmd 12/13)
04          04          8           (Change focus, repeats cmd 44)
05          05          7           Focus to max (infinity)
06          06          7           Focus to min
07          07          10
08          (ff)        298         Power down
09          09          11
0a          aa          10          Sync
0b          0b          16
0c          XX          4           Repeats last response byte
0d          0d          8
0e          0e          11
0f          0f          12

10 --       10 10       7, 3        (No-op)
11 --       11 11       8, 4
12 YY       12 12       9, 14+      Change aperture (+/- int8)
13 YY       13 13       9, 14+      Change aperture (+/- int8)
14 --       14 14       7, 4        (Change focus, repeats cmd 44)
15 --       15 15       7, 4        (Focus to max)
16 --       16 16       7, 4        (Focus to min)
17 --       17 17       7, 4
18 --       18 (ff)     8, 297      (Power down)
19 --       15 15       7, 5
1a --       1a aa       7, 4        (Sync)
1b --       1b 1b       7, 9
1c --       1c 1c       7, 3        (Repeats last response byte)
1d --       1d 1d       7, 4
1e --       1e 1e       7, 3
1f --       1f 1f       7, 4

20 --       20 20       4, 4        (No-op)
21 --       21 21       4, 3
22 --       22 22       4, 8+       (Change aperture, repeats cmd 12/13)
23 --       23 23       4, 7+       (Change aperture, repeats cmd 12/13)
24 --       24 24       4, 4        (Change focus, repeats cmd 44)
25 --       25 25       4, 4        (Focus to max)
26 --       26 26       4, 4        (Focus to min)
27 --       27 27       4, 4
28 --       28 (ff)     4, 296      (Power down)
29 --       29 29       3, 4
2a --       2a aa       4, 3        (Sync)
2b --       2b 2b       4, 8
2c --       2c 2c       4, 4        (Repeats last response byte)
2d --       2d 2d       4, 4
2e --       2e 2e       4, 3
2f --       2f 2f       4, 4

30 ?? ?? ?? 30 30 30 30 4, 4, 4, 6  ?
31 ?? ??    31 31 31    3, 3, 35    ?
32 ??       08 03       7, 4        ?
33 ?? ?? ?? 07 0a 0a 4a 6, 6, 3, 3  ?
34 ?? ??    34 34 34    5, 4, 12    ?
35          00          5           ?
36..3f      36..3f      3-5         (unused)

40 -- --    40 40 40    8, 4, 4     (No-op)
41 -- --    41 41 41    7, 4, 4
42 -- --    42 42 42    7, 3, 9+    (Change aperture, repeats cmd 12/13)
43 -- --    43 43 43    8, 3, 9+    (Change aperture, repeats cmd 12/13)
44 HH LL    44 44 44    7, 4, 4     Change focus (+/- int16 big-endian 0xHHLL)
45 -- --    45 45 45    7, 4, 5     (Focus to max)
46 -- --    46 46 46    8, 4, 4     (Focus to min)
47 -- --    47 47 47    8, 3, 6
48 -- --    48 48 (ff)  7, 4, 316   (Power down)
49 -- --    49 49 49    8, 3, 6
4a -- --    4a 4a aa    7, 3, 4     (Sync)
4b -- --    4b 4b 4b    7, 4, 10
4c -- --    4c 4c 4c    7, 4, 7     (Repeats last response byte)
4d -- --    4d 4d 4d    8, 4, 5
4e -- --    4e 4e 4e    7, 4, 5
4f -- --    4f 4f 4f    7, 3, 5

50 --       50 50       4, 4        (No-op)
51 --       51 51       4, 4
52 --       52 00       4, 9        (Change aperture, repeats cmd 12/13)
53 --       53 00       4, 9        (Change aperture, repeats cmd 12/13)
54 --       54 54       4, 3        (Change focus, repeats cmd 44)
55 --       55 55       4, 4        (Focus to max)
56 --       56 56       4, 4        (Focus to min)
57 --       57 57       4, 4
58 --       58 (ff)     4, 298      (Power down)
59 --       59 59       4, 5
5a --       5a aa       4, 4        (Sync)
5b --       5b 5b       4, 9
5c --       5c 5c       4, 4        (Repeats last response byte)
5d --       5d 5d       4, 3
5e --       5e 5e       4, 5
5f --       5f 5f       4, 5

60..65      60..65      4           (unused)
66          06          12          ?
67          67          4           (unused?)
68          30          4           ?
69 -- -- -- -- --
    23 09 00 00 00 00
        9, 69, 4, 4, 4, 6           ?
6a -- -- -- -- --
    23 09 00 00 00 00
        11, 65, 4, 3, 4, 6          ?
6b --       f1 9f       11, 4       ?
6c --       f2 b8       12, 4       ?
6d --       f1 9f       12, 4       alias of 6b?
6e --       f2 b8       12, 3       alias of 6c?
6f --       54 2a       6, 4

70..7f      70..7f      4           (unused)

80 -- -- -- -- -- -- --             Get lens basic info
    91 34 00 12 00 37 75 92
        12, 9, 4, 4, 4, 4, 3, 4
81 -- -- --
    3f 00 00 90
        7, 4, 3, 4
82          45          5           Get lens name (first char)
83          46          6           Get lens name (next char)
84          84          6
85 -- -- -- --
    ff ff ff ff ff
        9, 3, 4, 3, 4
86 -- -- -- 03 00 00 00 7, 3, 3, 4
87..8f      87..8f      5-6         (unused)

90 --       00 80       16, 12      Get basic status bits
91 -- --    00 80 44    18, 4, 4    Get extended status bits
92          92          8
93 XX YY ZZ 93 93 93 93 8, 3, 4, 4  Set IS parameters
94 --       f5 00       15, 16
95 -- -- -- 04 37 37 00 6, 6, 3, 3
96..9f      96..9f      3-5         (unused)

a0 --       00 12       7, 3
a1          1f          4
a2          1f          4
a3..af      a3..af      4-5         (unused)

b0 -- --    25 25 50    6, 4, 4
b1 --       91 93       8, 4
b2 -- --    42 02 5f    6, 4, 3
b3 --       99 82       7, 3
b4..bf      b4..bf      4-5         (unused)

c0 --       00 00       13, 3
c1 --       00 33       6, 4
c2 -- -- -- ff ff ff    7, 4, 4, 4
c3          40          8
c4 --       00 20       8, 4
c5 ??       c5 00       4, 4
c6          c6          4
c7 --       fe ff       4, 4
c8          c8          4
c9 -- -- -- -- --
    23 03 ff 00 00 00
        10, 4, 4, 3, 3, 4
ca -- -- -- -- --
    23 03 ff 00 00 00
        9, 3, 4, 4, 3, 4
cb..ce      cb..ce      4-5         (unused)
cf --       00 7c       9, 4

d0          ff          24          Get param array d0 (first byte)
d1          ff          24          Get param array d1 (first byte)
d2          ff          26          Get param array d2 (first byte)
d3          ff          26          Get param array d3 (first byte)
d4          fa          27          Get param array d4 (first byte)
d5..d7      d5..d7      4           (unused)
d8          ff          24          Get param array d8 (first byte)
d9          ff          24          Get param array d9 (first byte)
da          ff          25          Get param array da (first byte)
db          ff          24          Get param array db (first byte)
dc          fa          27          Get param array dc (first byte)
dd..de      dd..de      4-6         (unused)
df          af          7           Get next byte from param array

e0 --       3b c9       9, 4
e1..e3      e1..e3      4           (unused)
e4 --       1d 8e       6, 4
e5..e7      e5..e7      4           (unused)
e8 -- -- -- -- --
    29 96 9e b0 00 00
        10, 3, 3, 4, 4, 4
e9          e9          4           (unused)
ea -- -- -- -- --
    29 4e 9d f6 00 00
        10, 4, 4, 4, 3, 4
eb..ef      eb..ef      4           (unused)

f0          05          12
f1..f7      f1..f7      4-5         (unused)
f8          b5          10
f9          00          11
fa          b5          11
fb          fb          4           (unused)
fc          b7          12
fd          f5          11
fe          b7          12
ff          ff          7

```