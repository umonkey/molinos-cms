<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
  <xsl:import href="../admin/template.xsl" />

  <!-- вывод трансформаторов -->
  <xsl:template match="data[../@type = 'subscription']" mode="mcms_list">
    <thead>
      <tr>
        <th/>
        <th>Адрес</th>
      </tr>
    </thead>
    <tbody>
      <xsl:for-each select="node">
        <tr id="file-{@id}">
          <xsl:call-template name="odd_row" />
          <xsl:apply-templates select="." mode="mcms_list_name" />
        </tr>
      </xsl:for-each>
    </tbody>
  </xsl:template>
</xsl:stylesheet>
