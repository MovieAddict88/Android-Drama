package short.drama.ronald.com.model;

import com.google.gson.annotations.SerializedName;
import java.util.List;

public class Episode {

    @SerializedName("chapterId")
    private String chapterId;

    @SerializedName("chapterIndex")
    private int chapterIndex;

    @SerializedName("chapterName")
    private String chapterName;

    @SerializedName("isCharge")
    private int isCharge;

    @SerializedName("chargeChapter")
    private boolean chargeChapter;

    @SerializedName("chapterImg")
    private String chapterImg;

    @SerializedName("cdnList")
    private List<CdnEntry> cdnList;

    public String getChapterId() { return chapterId; }
    public int getChapterIndex() { return chapterIndex; }
    public String getChapterName() { return chapterName; }
    public boolean isCharged() { return isCharge == 1 || chargeChapter; }
    public String getChapterImg() { return chapterImg; }

    public String getBestVideoUrl() {
        if (cdnList == null || cdnList.isEmpty()) return null;
        for (CdnEntry cdn : cdnList) {
            if (cdn.isDefault == 1 && cdn.videoPathList != null) {
                for (VideoPath vp : cdn.videoPathList) {
                    if (vp.isDefault == 1) return vp.videoPath;
                }
                for (VideoPath vp : cdn.videoPathList) {
                    if (vp.quality == 720 && vp.isVipEquity == 0) return vp.videoPath;
                }
                for (VideoPath vp : cdn.videoPathList) {
                    if (vp.isVipEquity == 0) return vp.videoPath;
                }
            }
        }
        CdnEntry first = cdnList.get(0);
        if (first.videoPathList != null) {
            for (VideoPath vp : first.videoPathList) {
                if (vp.isVipEquity == 0) return vp.videoPath;
            }
        }
        return null;
    }

    public static class CdnEntry {
        @SerializedName("cdnDomain")
        String cdnDomain;
        @SerializedName("isDefault")
        int isDefault;
        @SerializedName("videoPathList")
        List<VideoPath> videoPathList;
    }

    public static class VideoPath {
        @SerializedName("quality")
        int quality;
        @SerializedName("videoPath")
        String videoPath;
        @SerializedName("isDefault")
        int isDefault;
        @SerializedName("isVipEquity")
        int isVipEquity;
    }
}
